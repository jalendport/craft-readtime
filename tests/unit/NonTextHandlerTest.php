<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

use craft\base\ElementInterface;
use craft\fields\Addresses;
use craft\fields\Assets;
use craft\fields\ButtonGroup;
use craft\fields\Categories;
use craft\fields\Checkboxes;
use craft\fields\Color;
use craft\fields\ContentBlock;
use craft\fields\Country;
use craft\fields\Date;
use craft\fields\Dropdown;
use craft\fields\Email;
use craft\fields\Entries;
use craft\fields\Icon;
use craft\fields\Json;
use craft\fields\Lightswitch;
use craft\fields\Link;
use craft\fields\Matrix;
use craft\fields\Money;
use craft\fields\MultiSelect;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\RadioButtons;
use craft\fields\Range;
use craft\fields\Table;
use craft\fields\Tags;
use craft\fields\Time;
use craft\fields\Users;
use jalendport\readtime\fieldhandlers\NonTextHandler;

/**
 * Unit coverage for {@see NonTextHandler}.
 *
 * `canHandle()` is a plain `instanceof` sweep over native field classes, so
 * constructor-free field instances are all that's needed — no booted Craft
 * application is involved.
 */

it('handles every native non-text field type', function() {
    $handler = new NonTextHandler();

    foreach ([
        Addresses::class,
        Assets::class,
        ButtonGroup::class,
        Categories::class,
        Checkboxes::class,
        Color::class,
        Country::class,
        Date::class,
        Dropdown::class,
        Email::class,
        Entries::class,
        Icon::class,
        Json::class,
        Lightswitch::class,
        Link::class,
        Money::class,
        MultiSelect::class,
        Number::class,
        RadioButtons::class,
        Range::class,
        Tags::class,
        Time::class,
        Users::class,
    ] as $class) {
        expect($handler->canHandle(fieldInstance($class)))->toBeTrue($class);
    }
});

it('leaves text and nested-block fields alone', function() {
    $handler = new NonTextHandler();

    foreach ([PlainText::class, Table::class, Matrix::class, ContentBlock::class] as $class) {
        expect($handler->canHandle(fieldInstance($class)))->toBeFalse($class);
    }
});

it('counts zero words whatever the value holds', function() {
    $field = fieldInstance(Link::class);
    $field->handle = 'linkField';

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->willReturn('https://example.com/a/long/path');

    expect((new NonTextHandler())->getWordCount($element, $field, readTimeServiceWithWpm(200)))->toBe(0);
});
