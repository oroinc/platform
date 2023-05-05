<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Formatter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\FormatterExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\FieldProperty;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormatterExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyContainer;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var FormatterExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->propertyContainer = $this->createMock(ContainerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new FormatterExtension(
            [],
            $this->propertyContainer,
            $this->translator
        );
    }

    public function testVisitResult(): void
    {
        $resultRecord1 = $this->createMock(ResultRecord::class);
        $resultRecord1->expects($this->exactly(4))
            ->method('getValue')
            ->withConsecutive(
                ['column1'],
                ['column2'],
                ['property1'],
                ['property2']
            )
            ->willReturnOnConsecutiveCalls(
                'val1',
                'val2',
                'val3',
                'val4',
            );
        $resultRecord2 = $this->createMock(ResultRecord::class);
        $resultRecord2->expects($this->exactly(4))
            ->method('getValue')
            ->withConsecutive(
                ['column1'],
                ['column2'],
                ['property1'],
                ['property2']
            )
            ->willReturnOnConsecutiveCalls(
                'val5',
                'val6',
                'val7',
                'val8',
            );

        $this->propertyContainer->expects($this->exactly(8))
            ->method('has')
            ->willReturn(true);
        $fieldType = 'field';
        $fieldProperty = new FieldProperty($this->translator);
        $this->propertyContainer->expects($this->exactly(8))
            ->method('get')
            ->with($fieldType)
            ->willReturn($fieldProperty);

        $gridConfig = DatagridConfiguration::createNamed('test_grid', []);
        $gridConfig->offsetSet(Configuration::COLUMNS_KEY, [
            'column1' => [
                'label' => 'value1',
                'order' => 1,
                'type' => $fieldType,
            ],
            'column2' => [
                'label' => 'value2',
                'order' => 2,
                'type' => $fieldType,
            ],
        ]);
        $gridConfig->offsetSet(Configuration::PROPERTIES_KEY, [
            'property1' => [
                'type' => $fieldType,
            ],
            'property2' => [
                'type' => $fieldType,
            ],
        ]);

        $resultsObject = $this->createMock(ResultsObject::class);
        $resultsObject->expects($this->once())
            ->method('getData')
            ->willReturn([$resultRecord1, $resultRecord2]);
        $resultsObject->expects($this->once())
            ->method('setData')
            ->with([
                [
                    'column1' => 'val1',
                    'column2' => 'val2',
                    'property1' => 'val3',
                    'property2' => 'val4',
                ],
                [
                    'column1' => 'val5',
                    'column2' => 'val6',
                    'property1' => 'val7',
                    'property2' => 'val8',
                ]
           ]);

        $this->extension->visitResult($gridConfig, $resultsObject);
    }
}
