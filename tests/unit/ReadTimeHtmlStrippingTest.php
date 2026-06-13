<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

declare(strict_types=1);

/**
 * Unit coverage for the HTML-stripping behaviour added to the word counter.
 *
 * `htmlToText()` and `countWords()` are private on the service and are reached
 * through the reflection helpers defined in tests/Pest.php.
 */

it('replaces tags with whitespace so words across a tag boundary are not merged', function () {
    expect(htmlToText('<p>foo</p><p>bar</p>'))->toBe('foo bar');
    expect(countWords('<p>foo</p><p>bar</p>'))->toBe(2);
});

it('excludes tag names, attribute names and attribute values such as URLs', function () {
    $html = '<a class="link" href="https://example.com/some/page">Read more</a>';

    expect(htmlToText($html))->toBe('Read more');
    expect(countWords($html))->toBe(2);
});

it('strips nested inline markup without dropping content', function () {
    $html = '<p>Hello <strong>brave</strong> <em>new</em> world</p>';

    expect(htmlToText($html))->toBe('Hello brave new world');
    expect(countWords($html))->toBe(4);
});

it('decodes entities after tags are stripped, so &amp; is not counted as a word', function () {
    expect(htmlToText('Salt &amp; Pepper'))->toBe('Salt & Pepper');
});

it('treats &nbsp; as whitespace so the words it separates are counted independently', function () {
    expect(htmlToText('foo&nbsp;bar'))->toBe('foo bar');
    expect(countWords('foo&nbsp;bar'))->toBe(2);
});

it('does not treat encoded angle brackets as tags', function () {
    expect(htmlToText('a &lt;b&gt; c'))->toBe('a <b> c');
});

it('leaves plain text unchanged apart from whitespace normalisation', function () {
    expect(htmlToText('one two three'))->toBe('one two three');
    expect(countWords('one two three'))->toBe(3);
    expect(htmlToText('  spaced   out  '))->toBe('spaced out');
});
