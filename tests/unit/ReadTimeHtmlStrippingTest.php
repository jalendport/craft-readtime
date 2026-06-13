<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

declare(strict_types=1);

namespace jalendport\readtime\tests\unit;

use jalendport\readtime\services\ReadTime;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit coverage for the HTML-stripping behaviour added to the word counter.
 *
 * The counting helpers are private, so they're exercised through reflection.
 * `htmlToText()` is pure string-cleaning (no Craft application needed), and
 * `countWords()` only delegates to `craft\helpers\StringHelper`, so neither
 * requires a booted Craft instance.
 */
final class ReadTimeHtmlStrippingTest extends TestCase
{
    private ReadTime $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReadTime();
    }

    private function htmlToText(string $text): string
    {
        $method = new ReflectionMethod(ReadTime::class, 'htmlToText');
        $method->setAccessible(true);

        return $method->invoke($this->service, $text);
    }

    private function countWords(mixed $value): int
    {
        $method = new ReflectionMethod(ReadTime::class, 'countWords');
        $method->setAccessible(true);

        return $method->invoke($this->service, $value);
    }

    /**
     * Tags are replaced with whitespace, not removed, so words on either side of
     * a tag boundary are not merged.
     */
    public function testTagsBecomeWhitespaceAndDoNotMergeWords(): void
    {
        self::assertSame('foo bar', $this->htmlToText('<p>foo</p><p>bar</p>'));
        self::assertSame(2, $this->countWords('<p>foo</p><p>bar</p>'));
    }

    /**
     * Tag names, attribute names and attribute values (such as URLs) are all
     * excluded from the cleaned text.
     */
    public function testTagAndAttributeTextIsExcluded(): void
    {
        $html = '<a class="link" href="https://example.com/some/page">Read more</a>';

        self::assertSame('Read more', $this->htmlToText($html));
        self::assertSame(2, $this->countWords($html));
    }

    /**
     * Nested inline markup is stripped without dropping content.
     */
    public function testNestedInlineMarkupIsStripped(): void
    {
        $html = '<p>Hello <strong>brave</strong> <em>new</em> world</p>';

        self::assertSame('Hello brave new world', $this->htmlToText($html));
        self::assertSame(4, $this->countWords($html));
    }

    /**
     * Entities are decoded after tags are stripped: `&amp;` becomes a literal
     * `&` rather than being counted as a word.
     */
    public function testHtmlEntitiesAreDecoded(): void
    {
        self::assertSame('Salt & Pepper', $this->htmlToText('Salt &amp; Pepper'));
    }

    /**
     * `&nbsp;` decodes to a non-breaking space and is treated as whitespace, so
     * the words it separates are counted independently.
     */
    public function testNonBreakingSpaceIsTreatedAsWhitespace(): void
    {
        self::assertSame('foo bar', $this->htmlToText('foo&nbsp;bar'));
        self::assertSame(2, $this->countWords('foo&nbsp;bar'));
    }

    /**
     * Tags are stripped before entities are decoded, so text legitimately
     * encoded as `&lt;`/`&gt;` is preserved rather than mistaken for a tag.
     */
    public function testEncodedAnglesAreNotTreatedAsTags(): void
    {
        self::assertSame('a <b> c', $this->htmlToText('a &lt;b&gt; c'));
    }

    /**
     * Plain (non-HTML) text is unchanged apart from whitespace normalisation, so
     * its word count is unaffected by the stripping.
     */
    public function testPlainTextIsUnchanged(): void
    {
        self::assertSame('one two three', $this->htmlToText('one two three'));
        self::assertSame(3, $this->countWords('one two three'));
        self::assertSame('spaced out', $this->htmlToText("  spaced   out  "));
    }
}
