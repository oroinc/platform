<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Utils;

use Oro\Bundle\IntegrationBundle\Utils\NonPrintableCharsStringSanitizer;

class NonPrintableCharsStringSanitizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NonPrintableCharsStringSanitizer
     */
    private $sanitizer;

    protected function setUp()
    {
        $this->sanitizer = new NonPrintableCharsStringSanitizer();
    }

    /**
     * @dataProvider removeNonPrintableCharactersDataProvider
     *
     * @param string $string
     * @param string $expectedString
     */
    public function testRemoveNonPrintableCharacters($string, $expectedString)
    {
        $actualString = $this->sanitizer->removeNonPrintableCharacters($string);

        static::assertEquals($expectedString, $actualString);
    }

    /**
     * @return array
     */
    public function removeNonPrintableCharactersDataProvider()
    {
        $simpleSpaceCode = 32;
        $spaceCharactersCodes = [
            9, // HT
            10, // LF
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
                'expectedString' => chr($simpleSpaceCode),
            ],
            'graph ignored characters' => [
                'string' => implode('', $graphIgnoredCharacters),
                'expectedString' => '',
            ],
        ];
    }
}
