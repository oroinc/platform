<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Utils;

use Oro\Bundle\IntegrationBundle\Utils\NonPrintableCharsStringSanitizer;

class NonPrintableCharsStringSanitizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var NonPrintableCharsStringSanitizer */
    private $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new NonPrintableCharsStringSanitizer();
    }

    /**
     * @dataProvider removeNonPrintableCharactersDataProvider
     */
    public function testRemoveNonPrintableCharacters(string $string, string $expectedString)
    {
        $actualString = $this->sanitizer->removeNonPrintableCharacters($string);

        self::assertEquals($expectedString, $actualString);
    }

    public function removeNonPrintableCharactersDataProvider(): array
    {
        $lineFeedCode = 10;
        $simpleSpaceCode = 32;
        $spaceCharactersCodes = [
            9, // HT
            $lineFeedCode,
            11, // VT
            12, // FF
            13, // CR
            $simpleSpaceCode
        ];

        $graphIgnoredCharacters = [
            "\u{061C}", // Arabic Letter Mark,
            "\u{180E}", // Mongolian Vowel Separator
            "\u{2066}", // Various "isolate"s
            "\u{2067}",
            "\u{2068}",
            "\u{2069}",
        ];

        return [
            'extended characters' => [
                'string' => 'Добрый день, Ibáñez',
                'expectedString' => 'Добрый день, Ibáñez',
            ],
            'spaces' => [
                'string' => implode('', array_map('chr', $spaceCharactersCodes)),
                'expectedString' => chr($lineFeedCode) . chr($simpleSpaceCode),
            ],
            'graph ignored characters' => [
                'string' => implode('', $graphIgnoredCharacters),
                'expectedString' => '',
            ],
            'text with new line' => [
                'string' => 'First string' . chr($lineFeedCode) . 'Second string',
                'expectedString' => "First string\nSecond string",
            ],
        ];
    }
}
