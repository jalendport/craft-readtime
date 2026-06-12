<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

declare(strict_types=1);

namespace jalendport\readtime\fieldhandlers;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use jalendport\readtime\base\FieldHandlerInterface;
use jalendport\readtime\services\ReadTime;
use verbb\vizy\fields\VizyField;
use verbb\vizy\nodes\VizyBlock;

/**
 * Vizy field handler (verbb/vizy).
 *
 * Vizy is an optional, soft dependency. A Vizy field value is a node collection
 * mixing rich-text nodes with "block" nodes that wrap a nested element and its
 * own field layout. We count the text of the rich-text nodes and recurse into
 * each block's nested fields, so e.g. a plain-text sub-field inside a Vizy block
 * is counted correctly (which rendering the field as HTML would miss).
 */
class VizyHandler implements FieldHandlerInterface
{
    public function canHandle(FieldInterface $field): bool
    {
        return $field instanceof VizyField;
    }

    public function getReadTimeSeconds(ElementInterface $element, FieldInterface $field, ReadTime $service): int
    {
        $value = $element->getFieldValue($field->handle);

        if ($value === null) {
            return 0;
        }

        $seconds = 0;

        foreach ($this->getNodes($value) as $node) {
            if ($node instanceof VizyBlock) {
                $blockElement = $this->getBlockElement($node);

                if ($blockElement !== null) {
                    $seconds += $service->secondsForElement($blockElement);
                }

                continue;
            }

            // Plain rich-text node: count its rendered text.
            $seconds += $service->secondsForString($this->nodeText($node));
        }

        return $seconds;
    }

    /**
     * @return iterable<object>
     */
    private function getNodes(object $value): iterable
    {
        if (method_exists($value, 'getNodes')) {
            return $value->getNodes() ?? [];
        }

        return is_iterable($value) ? $value : [];
    }

    private function getBlockElement(VizyBlock $node): ?ElementInterface
    {
        foreach (['getBlockElement', 'getElement'] as $method) {
            if (method_exists($node, $method)) {
                $blockElement = $node->$method();

                if ($blockElement instanceof ElementInterface) {
                    return $blockElement;
                }
            }
        }

        return null;
    }

    private function nodeText(object $node): string
    {
        foreach (['getText', 'renderNode', 'renderHtml'] as $method) {
            if (method_exists($node, $method)) {
                return (string)$node->$method();
            }
        }

        return '';
    }
}
