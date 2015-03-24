<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\ImportExport\DataConverter\IntegrationAwareDataConverter;

class IntegrationAwareDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IntegrationAwareDataConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataConverter;

    protected function setUp()
    {
        $this->dataConverter = $this->getMockForAbstractClass(
            'Oro\Bundle\IntegrationBundle\ImportExport\DataConverter\IntegrationAwareDataConverter'
        );
    }

    protected function tearDown()
    {
        unset($this->dataConverter);
    }

    /**
     * @dataProvider inputDataProvider
     * @param array $input
     * @param array $expected
     * @param ContextInterface $context
     */
    public function testConvertToImportFormat(array $input, array $expected, ContextInterface $context = null)
    {
        $this->dataConverter->expects($this->once())
            ->method('getHeaderConversionRules')
            ->will($this->returnValue(['key' => 'cKey']));

        if ($context) {
            $this->dataConverter->setImportExportContext($context);
        }
        $this->assertEquals($expected, $this->dataConverter->convertToImportFormat($input));
    }

    /**
     * @return array
     */
    public function inputDataProvider()
    {
        $emptyContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $context->expects($this->any())
            ->method('hasOption')
            ->with('channel')
            ->will($this->returnValue(true));
        $context->expects($this->any())
            ->method('getOption')
            ->with('channel')
            ->will($this->returnValue(2));

        return [
            [
                ['key' => 'val'],
                ['cKey' => 'val'],
                null
            ],
            [
                ['key' => 'val'],
                ['cKey' => 'val'],
                $emptyContext
            ],
            [
                ['key' => 'val'],
                ['cKey' => 'val', 'channel' => ['id' => 2]],
                $context
            ]
        ];
    }
}
