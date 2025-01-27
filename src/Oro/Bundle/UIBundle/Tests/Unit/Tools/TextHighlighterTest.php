<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools;

use Oro\Bundle\UIBundle\Tools\TextHighlighter;
use PHPUnit\Framework\TestCase;

final class TextHighlighterTest extends TestCase
{
    /**
     * @dataProvider textDataProvider
     */
    public function testHighlight(string $subject, string $original, string $expected): void
    {
        $highlighter = new TextHighlighter();

        $result = $highlighter->highlightDifferences($subject, $original, '<u>%s</u>');

        self::assertEquals($expected, $result);
    }

    public function textDataProvider(): \Generator
    {
        yield 'empty' => [
            'subject' => '',
            'original' => '',
            'expected' => '',
        ];

        yield 'strings with spaces only' => [
            'subject' => ' ',
            'original' => '  ',
            'expected' => '',
        ];

        yield 'empty subject' => [
            'subject' => '',
            'original' => 'red fox',
            'expected' => '',
        ];

        yield 'empty original' => [
            'subject' => 'red fox',
            'original' => '',
            'expected' => '<u>red</u> <u>fox</u>',
        ];

        yield 'subject equal to original' => [
            'subject' => 'red fox',
            'original' => 'red fox',
            'expected' => 'red fox',
        ];

        yield 'equal strings with spaces' => [
            'subject' => ' red fox ', // single space
            'original' => '  red  fox  ', // double space
            'expected' => 'red fox',
        ];

        yield 'subject has different first word' => [
            'subject' => 'red fox jumps',
            'original' => 'ginger fox jumps',
            'expected' => '<u>red</u> fox jumps',
        ];

        yield 'subject has different middle word' => [
            'subject' => 'red fox jumps',
            'original' => 'red dog jumps',
            'expected' => 'red <u>fox</u> jumps',
        ];

        yield 'subject has different last word' => [
            'subject' => 'red fox jumps',
            'original' => 'red fox sits',
            'expected' => 'red fox <u>jumps</u>',
        ];

        yield 'subject contains extra word at the beginning' => [
            'subject' => 'cute red fox jumps',
            'original' => 'red fox jumps',
            'expected' => '<u>cute</u> <u>red</u> <u>fox</u> <u>jumps</u>',
        ];

        yield 'subject contains extra word in the middle' => [
            'subject' => 'red cute fox jumps',
            'original' => 'red fox jumps',
            'expected' => 'red <u>cute</u> <u>fox</u> <u>jumps</u>',
        ];

        yield 'subject contains extra word in the end' => [
            'subject' => 'red fox jumps high',
            'original' => 'red fox jumps',
            'expected' => 'red fox jumps <u>high</u>',
        ];

        yield 'original contains extra word at the beginning' => [
            'subject' => 'red fox jumps',
            'original' => 'cute red fox jumps',
            'expected' => '<u>red</u> <u>fox</u> <u>jumps</u>',
        ];

        yield 'original contains extra word in the middle' => [
            'subject' => 'red fox jumps',
            'original' => 'red cute fox jumps',
            'expected' => 'red <u>fox</u> <u>jumps</u>',
        ];

        yield 'original contains extra word in the end' => [
            'subject' => 'red fox jumps',
            'original' => 'red fox jumps high',
            'expected' => 'red fox jumps',
        ];
    }
}
