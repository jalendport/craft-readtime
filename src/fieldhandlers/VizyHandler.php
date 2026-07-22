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
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 3.0.0
 */
class VizyHandler implements FieldHandlerInterface
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    public function canHandle(FieldInterface $field): bool
    {
        return $field instanceof VizyField;
    }

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    public function getReadTimeSeconds(ElementInterface $element, FieldInterface $field, ReadTime $service): int
    {
        $value = $element->getFieldValue($field->handle);

        if ($value === null) {
            return 0;
        }

        $seconds = 0;

        foreach ($this->_getNodes($value) as $node) {
            if ($node instanceof VizyBlock) {
                $blockElement = $this->_getBlockElement($node);

                if ($blockElement !== null) {
                    $seconds += $service->secondsForElement($blockElement);
                }

                continue;
            }

            // Plain rich-text node: count its rendered text.
            $seconds += $service->secondsForString($this->_nodeText($node));
        }

        return $seconds;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the nested element wrapped by a Vizy block node.
     *
     * @param VizyBlock $node the block node
     * @return ElementInterface|null the nested element, or null if it can't be resolved
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    private function _getBlockElement(VizyBlock $node): ?ElementInterface
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

    /**
     * Returns the nodes in a Vizy field value.
     *
     * @param object $value the Vizy field value
     * @return iterable<object> the field's nodes
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    private function _getNodes(object $value): iterable
    {
        if (method_exists($value, 'getNodes')) {
            return $value->getNodes() ?? [];
        }

        return is_iterable($value) ? $value : [];
    }

    /**
     * Returns the text of a plain rich-text Vizy node.
     *
     * An empty result falls through to the next method rather than returning:
     * `getText()` only yields a node's own raw text, and container nodes like
     * paragraphs keep their words in child text nodes — so for them it is
     * always empty and the content must come from `renderNode()`, which
     * renders the node including its children.
     *
     * @param object $node the node to read
     * @return string the node's text, or an empty string if it can't be read
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    private function _nodeText(object $node): string
    {
        foreach (['getText', 'renderNode', 'renderHtml'] as $method) {
            if (method_exists($node, $method)) {
                $text = (string)$node->$method();

                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }
}
