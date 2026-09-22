<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime\fieldhandlers;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\fields\ContentBlock;
use jalendport\readtime\base\WordCountHandlerInterface;
use jalendport\readtime\services\ReadTime;

/**
 * Native Content Block field handler (Craft 5.8+).
 *
 * Unlike Matrix, a Content Block field holds exactly one nested
 * {@see \craft\elements\ContentBlock} element rather than a query of them, so
 * the generic element-list normaliser never sees it. Without this handler the
 * value fell through to plain-text counting, where the element stringified to
 * "Content block 123" and everything inside it was lost. We walk the nested
 * element's own field layout instead, which also covers Content Blocks nested
 * inside Matrix or Neo blocks — the page-builder shape from #40.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 3.3.0
 */
class ContentBlockHandler implements WordCountHandlerInterface
{
    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.3.0
     */
    public function canHandle(FieldInterface $field): bool
    {
        return $field instanceof ContentBlock;
    }

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.3.0
     */
    public function getWordCount(ElementInterface $element, FieldInterface $field, ReadTime $service): int
    {
        $value = $element->getFieldValue($field->handle);

        if (!$value instanceof ElementInterface) {
            return 0;
        }

        return $service->wordsForElement($value);
    }
}
