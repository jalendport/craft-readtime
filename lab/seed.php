<?php
/**
 * Read Time — Spark Craft Lab seed hook
 *
 * Read Time's whole job is walking field layouts, so the lab needs every
 * supported field type populated with content of a known size. This hook runs
 * in up to three passes, each handing off to a fresh `craft lab/seed` process
 * when the current one can't safely continue:
 *
 * 1. Bootstrap — the lab only installs the plugin under test, so on first run
 *    the supported plugins (CKEditor, Neo, Vizy) aren't in vendor. This pass
 *    composer-requires and installs them inside the instance, then re-runs
 *    the seed in a fresh process (so the new autoloader is live).
 * 2. Schema — with all plugins loadable, build a field per handler (plain
 *    text, CKEditor, Matrix, Neo with a Matrix nested inside a block, Vizy)
 *    and attach them to the generic `labArticle` entry type on their own tab.
 *    If anything had to be created, re-run the seed once more: an entry saved
 *    in the same process that created its schema loses its field values to
 *    stale layout state.
 * 3. Content — seed the "Read Time Lab" entry.
 *
 * Every word count is a multiple of 10 so that, at the default 200 words per
 * minute, each field converts to a whole number of seconds (10 words = 3s).
 * The expected values asserted in lab/test.twig are derived from the counts
 * below — keep the two files in sync.
 *
 * | Field        | Words                      | Seconds |
 * |--------------|----------------------------|---------|
 * | labSummary   | 10                         | 3       |
 * | labBody      | 20                         | 6       |
 * | labPlainText | 50                         | 15      |
 * | labCkeditor  | 100 (60 + 40 paragraphs)   | 30      |
 * | labMatrix    | 90 (40 + 30 CKE, then 20)  | 27      |
 * | labNeo       | 90 (60 + 30 nested Matrix) | 27      |
 * | labVizy      | 100 (50 + 50 paragraphs)   | 30      |
 * | **Total**    | **460**                    | **138** |
 *
 * @link https://github.com/jalendport/spark-craft-lab
 */

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

return function ($controller = null): void {
    $out = static function (string $message) use ($controller): void {
        if ($controller !== null && method_exists($controller, 'stdout')) {
            $controller->stdout("  [read-time] $message\n");
        }
    };

    $fail = static function (string $label, $model): void {
        throw new RuntimeException(sprintf(
            'Could not save %s: %s',
            $label,
            json_encode($model->getErrors()) ?: 'unknown validation error',
        ));
    };

    // Deterministic filler: a repeating lorem cycle so seeded prose looks like
    // prose but always counts to exactly $count words.
    $words = static function (int $count): string {
        $bank = ['lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'sed', 'tempor'];
        $parts = [];

        for ($i = 0; $i < $count; $i++) {
            $parts[] = $bank[$i % count($bank)];
        }

        return implode(' ', $parts);
    };

    $root = Craft::getAlias('@root');
    $run = static function (string $command) use ($root, $out): void {
        // This process's pending project-config changes hold a lock that would
        // block any child craft process — flush (and thereby release the lock)
        // before shelling out.
        Craft::$app->getProjectConfig()->saveModifiedConfigData();

        $out("> $command");
        exec(sprintf('cd %s && %s 2>&1', escapeshellarg($root), $command), $output, $code);

        if ($code !== 0) {
            throw new RuntimeException("Command failed ($code): $command\n" . implode("\n", $output));
        }
    };

    $supportedPlugins = [
        'ckeditor' => ['package' => 'craftcms/ckeditor:^4.0', 'class' => 'craft\ckeditor\Field'],
        'neo' => ['package' => 'spicyweb/craft-neo:^5.0', 'class' => 'benf\neo\Field'],
        'vizy' => ['package' => 'verbb/vizy:^3.0', 'class' => 'verbb\vizy\fields\VizyField'],
    ];

    // Phase 1 — bootstrap missing supported plugins into the instance.
    $missing = array_filter($supportedPlugins, static fn(array $plugin): bool => !class_exists($plugin['class']));

    if ($missing !== []) {
        if (getenv('LAB_READTIME_BOOTSTRAPPED') !== false) {
            throw new RuntimeException(sprintf(
                'Supported plugins still missing after bootstrap: %s',
                implode(', ', array_keys($missing)),
            ));
        }

        $packages = array_map(
            static fn(array $plugin): string => escapeshellarg($plugin['package']),
            array_values($missing),
        );
        $run('composer require ' . implode(' ', $packages) . ' --no-interaction --no-progress');

        foreach (array_keys($missing) as $handle) {
            $run("php craft plugin/install $handle");
        }

        // Re-run the full seed in a fresh process so the new packages autoload;
        // this closure then takes the phase-2 path there.
        $run('LAB_READTIME_BOOTSTRAPPED=1 php craft lab/seed');

        return;
    }

    // Phase 2 — plugins are loadable; make sure they're installed (idempotent).
    $plugins = Craft::$app->getPlugins();

    foreach (array_keys($supportedPlugins) as $handle) {
        if (!$plugins->isPluginInstalled($handle)) {
            $out("Installing $handle");
            $plugins->installPlugin($handle);
        }
    }

    $fields = Craft::$app->getFields();
    $entries = Craft::$app->getEntries();

    // Tracks whether this process had to create any schema. If it did, the
    // content pass is handed off to a fresh process (see below).
    $schemaCreated = false;

    $ensureField = static function (string $class, string $handle, string $name, array $config = [], ?callable $configure = null) use ($fields, $fail, $out, &$schemaCreated) {
        $field = $fields->getFieldByHandle($handle);

        if ($field instanceof $class) {
            return $field;
        }

        $schemaCreated = true;
        $out("Creating field $handle");
        $field = new $class(['name' => $name, 'handle' => $handle, 'searchable' => true] + $config);

        if ($configure !== null) {
            $configure($field);
        }

        if (!$fields->saveField($field)) {
            $fail("field $handle", $field);
        }

        return $field;
    };

    // Plain text fields — one top-level, one per nested-block layout.
    $plainTextField = $ensureField(PlainText::class, 'labPlainText', 'Lab Plain Text', ['multiline' => true]);
    $matrixBodyField = $ensureField(PlainText::class, 'labMatrixBody', 'Lab Matrix Body', ['multiline' => true]);
    $neoBodyField = $ensureField(PlainText::class, 'labNeoBody', 'Lab Neo Body', ['multiline' => true]);

    // CKEditor — needs a named editor config to exist.
    $ckeConfigs = \craft\ckeditor\Plugin::getInstance()->getCkeConfigs();
    $ckeConfig = $ckeConfigs->getAll()[0] ?? null;

    if ($ckeConfig === null) {
        $schemaCreated = true;
        $ckeConfig = new \craft\ckeditor\CkeConfig(['name' => 'Lab', 'uid' => StringHelper::UUID()]);

        if (!$ckeConfigs->save($ckeConfig)) {
            $fail('CKEditor config', $ckeConfig);
        }
    }

    $ckeditorField = $ensureField(\craft\ckeditor\Field::class, 'labCkeditor', 'Lab CKEditor', ['ckeConfig' => $ckeConfig->uid]);

    // A reusable "Content" tab builder for nested-block field layouts.
    $buildLayout = static function (string $elementType, array $layoutFields): FieldLayout {
        $layout = new FieldLayout(['type' => $elementType]);
        $tab = new FieldLayoutTab(['name' => 'Content', 'layout' => $layout]);
        $tab->setElements(array_map(
            static fn($field): CustomField => new CustomField($field),
            $layoutFields,
        ));
        $layout->setTabs([$tab]);

        return $layout;
    };

    // Matrix — one block type carrying plain text + CKEditor sub-fields.
    $matrixEntryType = $entries->getEntryTypeByHandle('labMatrixText');

    if ($matrixEntryType === null) {
        $schemaCreated = true;
        $out('Creating labMatrixText entry type');
        $matrixEntryType = new EntryType([
            'name' => 'Lab Matrix Text',
            'handle' => 'labMatrixText',
            'hasTitleField' => false,
        ]);
        $matrixEntryType->setFieldLayout($buildLayout(Entry::class, [$matrixBodyField, $ckeditorField]));

        if (!$entries->saveEntryType($matrixEntryType)) {
            $fail('labMatrixText entry type', $matrixEntryType);
        }
    }

    $matrixField = $ensureField(
        Matrix::class,
        'labMatrix',
        'Lab Matrix',
        ['viewMode' => Matrix::VIEW_MODE_BLOCKS],
        static fn(Matrix $field) => $field->setEntryTypes([$matrixEntryType]),
    );

    // Neo — one block type whose layout nests the Matrix field, exercising
    // Matrix-in-Neo recursion.
    $neoField = $ensureField(
        \benf\neo\Field::class,
        'labNeo',
        'Lab Neo',
        [],
        static function (\benf\neo\Field $field) use ($buildLayout, $neoBodyField, $matrixField): void {
            $blockType = new \benf\neo\models\BlockType([
                'name' => 'Lab Neo Text',
                'handle' => 'labNeoText',
                'topLevel' => true,
                'sortOrder' => 1,
            ]);
            $blockType->setFieldLayout($buildLayout(\benf\neo\elements\Block::class, [$neoBodyField, $matrixField]));
            $field->setBlockTypes([$blockType]);
        },
    );

    // Vizy — rich-text nodes only; the handler counts them node by node.
    $vizyField = $ensureField(\verbb\vizy\fields\VizyField::class, 'labVizy', 'Lab Vizy');

    // Attach everything to the generic labArticle entry type on its own tab,
    // rebuilt on every seed so reseeding stays idempotent.
    $articleType = $entries->getEntryTypeByHandle('labArticle');
    $layout = $articleType->getFieldLayout();
    $tabs = array_values(array_filter(
        $layout->getTabs(),
        static fn(FieldLayoutTab $tab): bool => $tab->name !== 'Read Time Lab',
    ));

    $labTab = new FieldLayoutTab(['name' => 'Read Time Lab', 'layout' => $layout]);
    $labTab->setElements(array_map(
        static fn($field): CustomField => new CustomField($field),
        [$plainTextField, $ckeditorField, $matrixField, $neoField, $vizyField],
    ));
    $tabs[] = $labTab;
    $layout->setTabs($tabs);
    $articleType->setFieldLayout($layout);

    if (!$entries->saveEntryType($articleType)) {
        $fail('labArticle entry type', $articleType);
    }

    // An entry saved in the same process that created its schema silently
    // loses its custom-field values to stale layout state (refreshing Craft's
    // field/entry-type memoization is not enough) — hand the content pass to a
    // fresh process where the schema pre-exists.
    if ($schemaCreated) {
        if (getenv('LAB_READTIME_SCHEMA_BUILT') !== false) {
            throw new RuntimeException('Lab schema still missing after the schema pass.');
        }

        $out('Schema created — re-running the seed for the content pass');
        $run('LAB_READTIME_SCHEMA_BUILT=1 php craft lab/seed');

        return;
    }

    // Seed the lab entry with the word counts from the table up top.
    $out('Seeding the Read Time Lab entry');
    $section = $entries->getSectionByHandle('labArticles');
    $site = Craft::$app->getSites()->getPrimarySite();

    $entry = Entry::find()
        ->section('labArticles')
        ->siteId($site->id)
        ->slug('read-time-lab')
        ->status(null)
        ->one() ?? new Entry();

    $entry->sectionId = $section->id;
    $entry->typeId = $articleType->id;
    $entry->siteId = $site->id;
    $entry->enabled = true;
    $entry->title = 'Read Time Lab';
    $entry->slug = 'read-time-lab';

    $entry->setFieldValue('labSummary', $words(10));
    $entry->setFieldValue('labBody', $words(20));
    $entry->setFieldValue('labPlainText', $words(50));
    $entry->setFieldValue('labCkeditor', '<p>' . $words(60) . '</p><p>' . $words(40) . '</p>');
    $entry->setFieldValue('labMatrix', [
        'new1' => [
            'type' => 'labMatrixText',
            'enabled' => true,
            'fields' => [
                'labMatrixBody' => $words(40),
                'labCkeditor' => '<p>' . $words(30) . '</p>',
            ],
        ],
        'new2' => [
            'type' => 'labMatrixText',
            'enabled' => true,
            'fields' => [
                'labMatrixBody' => $words(20),
                'labCkeditor' => '',
            ],
        ],
    ]);
    $entry->setFieldValue('labNeo', [
        'new1' => [
            'type' => 'labNeoText',
            'enabled' => true,
            'level' => 1,
            'fields' => [
                'labNeoBody' => $words(60),
                'labMatrix' => [
                    'new1' => [
                        'type' => 'labMatrixText',
                        'enabled' => true,
                        'fields' => [
                            'labMatrixBody' => $words(30),
                            'labCkeditor' => '',
                        ],
                    ],
                ],
            ],
        ],
    ]);
    $entry->setFieldValue('labVizy', json_encode([
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $words(50)]]],
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $words(50)]]],
    ]));

    if (!Craft::$app->getElements()->saveElement($entry)) {
        $fail('Read Time Lab entry', $entry);
    }
};
