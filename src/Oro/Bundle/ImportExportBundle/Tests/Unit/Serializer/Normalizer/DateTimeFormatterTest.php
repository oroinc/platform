<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DateTimeFormatter;

class DateTimeFormatterTest extends \PHPUnit_Framework_TestCase
{
    /** @var DateTimeFormatter */
    protected $formatter;

    protected function setUp()
    {
        /** @var FormatterProvider|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider        = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new DateTimeFormatter($provider);
    }

    /**
     * @dataProvider testSupportsNormalizationProvider
     * @param mixed $data
     * @param array $context
     * @param bool  $result
     */
    public function testSupportsNormalization($data, array $context, $result)
    {
        $this->assertEquals($result, $this->formatter->supportsNormalization($data, null, $context));
    }

    /**
     * @return array
     */
    public function testSupportsNormalizationProvider()
    {

        $dateTime = new \DateTime();
        $providerKey = FormatterProvider::FORMATTER_PROVIDER;
        return [
            'supports datetime' => [
                $dateTime,
                [$providerKey => ['datetime' => 'test'], 'type' => 'datetime'],
                true
            ],
            'supports date' => [
                $dateTime,
                [$providerKey => ['date' => 'test'], 'type' => 'date'],
                true
            ],
            'supports time' => [
                $dateTime,
                [$providerKey => ['time' => 'test'], 'type' => 'time'],
                true
            ],
            'not supports object' => [
                new \StdClass,
                [$providerKey => ['datetime' => 'test'], 'type' => 'datetime'],
                false
            ],
            'not supports string' => [
                $dateTime->format('d/m/Y H:i:s'),
                [$providerKey => ['datetime' => 'test'], 'type' => 'datetime'],
                false
            ],
            'not supports bad type' => [
                $dateTime,
                [$providerKey => ['datetime' => 'test'], 'type' => 'test'],
                false
            ],
            'not supports not provided type' => [
                $dateTime,
                [$providerKey => ['test' => 'test'], 'type' => 'datetime'],
                false
            ],
        ];
    }
}
