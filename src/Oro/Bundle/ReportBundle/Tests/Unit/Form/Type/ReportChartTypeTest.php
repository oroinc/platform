<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ChartBundle\Form\EventListener\ChartTypeEventListener;
use Oro\Bundle\ChartBundle\Form\Type\ChartSettingsCollectionType;
use Oro\Bundle\ChartBundle\Form\Type\ChartType;
use Oro\Bundle\ChartBundle\Model\ConfigProvider;
use Oro\Bundle\ReportBundle\Form\Type\ReportChartSchemaCollectionType;
use Oro\Bundle\ReportBundle\Form\Type\ReportChartType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\MutableFormEventSubscriber;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ReportChartTypeTest extends FormIntegrationTestCase
{
    public function testBuildForm()
    {
        $form = $this->factory->create(ReportChartType::class, null, []);

        $this->assertTrue($form->has('data_schema'));
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->any())
            ->method('getChartNames')
            ->willReturn([]);
        $configProvider->expects($this->never())
            ->method('getChartConfig');

        $eventListener = new MutableFormEventSubscriber($this->createMock(ChartTypeEventListener::class));

        $childType = new ChartType($configProvider);
        $childType->setEventListener($eventListener);

        $collectionType = new ChartSettingsCollectionType();
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
