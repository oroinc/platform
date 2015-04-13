<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Twig\FormatterExtension;

class FormatterExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormatterExtension
     */
    protected $formatterExtension;

    protected function setUp()
    {
        $this->formatterExtension = new FormatterExtension();
    }

    /**
     * @param string $filename
     * @param string $result
     *
     * @dataProvider filenameProvider
     */
    public function testFormatFilename($filename, $result)
    {
        $actualResult = $this->formatterExtension->formatFilename($filename);
        $this->assertEquals($actualResult, $result);
    }

    public function filenameProvider()
    {
        return [
            [
                'filename' => '',
                'result' => '',
            ],
            [
                'filename' => 'somename.jpg',
                'result' => 'somename.jpg',
            ],
            [
                'filename' => 'somename_very_long_file_name.jpg',
                'result' => 'somenam..ame.jpg',
            ],
            [
                'filename' => 'somename123.jpg',
                'result' => 'somename123.jpg',
            ],
            [
                'filename' => 'somename1234.jpg',
                'result' => 'somenam..234.jpg',
            ],
            [
                'filename' => 'тратата.jpg',
                'result' => 'тратата.jpg',
            ],
            [
                'filename' => 'тратататратататратата.jpg',
                'result' => 'тратата..ата.jpg',
            ],
        ];
    }
}
