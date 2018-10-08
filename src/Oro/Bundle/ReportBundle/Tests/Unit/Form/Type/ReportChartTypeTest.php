<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ChartBundle\Form\Type\ChartSettingsCollectionType;
use Oro\Bundle\ChartBundle\Form\Type\ChartType;
use Oro\Bundle\ReportBundle\Form\Type\ReportChartSchemaCollectionType;
use Oro\Bundle\ReportBundle\Form\Type\ReportChartType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\MutableFormEventSubscriber;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ReportChartTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $configProvider;

    public function testBuildForm()
    {
        $form = $this->factory->create(ReportChartType::class, null, []);

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
