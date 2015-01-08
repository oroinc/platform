<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ChartBundle\Form\Type\ChartSettingsCollectionType;
use Oro\Bundle\ReportBundle\Form\Type\ReportChartSchemaCollectionType;
use Oro\Bundle\ChartBundle\Form\Type\ChartType;
use Oro\Bundle\ReportBundle\Form\Type\ReportChartType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\MutableFormEventSubscriber;

class ReportChartTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ReportChartType
     */
    protected $type;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    protected function setUp()
    {
        $this->type = new ReportChartType();

        parent::setUp();
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type, null, []);

        $this->assertTrue($form->has('data_schema'));
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $configProvider = $this
            ->getMockBuilder('\Oro\Bundle\ChartBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider
            ->expects($this->atLeastOnce())
            ->method('getChartConfigs')
            ->will($this->returnValue([]));

        $mock = $this
            ->getMockBuilder('Oro\Bundle\ChartBundle\Form\EventListener\ChartTypeEventListener')
            ->getMock();

        $eventListener = new MutableFormEventSubscriber($mock);

        $childType = new ChartType($configProvider);
        $childType->setEventListener($eventListener);

        $collectionType       = new ChartSettingsCollectionType();
        $schemaCollectionType = new ReportChartSchemaCollectionType($configProvider);

        return [
            new PreloadedExtension(
                [
                    $childType->getName()            => $childType,
                    $collectionType->getName()       => $collectionType,
                    $schemaCollectionType->getName() => $schemaCollectionType,
                ],
                []
            )
        ];
    }
}
