<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\MimeType;

use Oro\Bundle\ImportExportBundle\MimeType\CsvMimeTypeGuesser;

class CsvMimeTypeGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var CsvMimeTypeGuesser */
    private $guesser;

    protected function setUp(): void
    {
        $this->guesser = new CsvMimeTypeGuesser();
    }

    /**
     * @dataProvider filesDataProvider
     */
    public function testGuess(string $path, ?string $expectedMimeType)
    {
        $this->assertEquals($expectedMimeType, $this->guesser->guessMimeType($path));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function filesDataProvider(): array
    {
        $validCsv = realpath(__DIR__ . '/Fixtures/valid.csv');
        $brokenCsv = realpath(__DIR__ . '/Fixtures/broken.csv');
        $validHtmlCsv = realpath(__DIR__ . '/Fixtures/valid_html.csv');
        $textFile = realpath(__DIR__ . '/Fixtures/test.txt');
        $commaSeparatedTxt = realpath(__DIR__ . '/Fixtures/comma_separated.txt');

        return [
            'valid csv' => [
                'path' => $validCsv,
                'expectedMimeType' => 'text/csv'
            ],
            'broken csv' => [
                'path' => $brokenCsv,
                'expectedMimeType' => null
            ],
            'valid csv with html' => [
                'path' => $validHtmlCsv,
                'expectedMimeType' => 'text/csv'
            ],
            'text file' => [
                'path' => $textFile,
                'expectedMimeType' => null
            ],
            'comma separated txt' => [
                'path' => $commaSeparatedTxt,
                'expectedMimeType' => null
            ],
        ];
    }

    public function testGuessNonDefaultSettings()
    {
        $this->guesser->setDelimiter('|');
        $this->guesser->setEnclosure("'");
        $this->guesser->setEscape('_');

        $path = realpath(__DIR__ . '/Fixtures/customized.csv');
        $this->assertEquals('text/csv', $this->guesser->guessMimeType($path));
    }
}
