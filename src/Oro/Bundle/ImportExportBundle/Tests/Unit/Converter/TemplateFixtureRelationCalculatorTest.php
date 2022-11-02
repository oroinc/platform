<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Converter\TemplateFixtureRelationCalculator;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;

class TemplateFixtureRelationCalculatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TemplateManager|\PHPUnit\Framework\MockObject\MockObject */
    private $templateManager;

    /** @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldHelper;

    /** @var TemplateFixtureRelationCalculator */
    private $calculator;

    protected function setUp(): void
    {
        $this->templateManager = $this->createMock(TemplateManager::class);
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        $this->calculator = new TemplateFixtureRelationCalculator($this->templateManager, $this->fieldHelper);
    }

    /**
     * @dataProvider calculatorDataProvider
     */
    public function testGetMaxRelatedEntities(\ArrayIterator $fixtureData, string $field, int $expected)
    {
        $entityName = 'stdClass';

        $fixture = $this->createMock(TemplateFixtureInterface::class);
        $fixture->expects($this->once())
            ->method('getData')
            ->willReturn($fixtureData);

        $this->templateManager->expects($this->once())
            ->method('getEntityFixture')
            ->with($entityName)
            ->willReturn($fixture);
        $this->fieldHelper->expects($this->atLeastOnce())
            ->method('getObjectValue')
            ->willReturnCallback(function ($obj, $field) {
                return $obj->{$field};
            });

        $this->assertEquals($expected, $this->calculator->getMaxRelatedEntities($entityName, $field));
    }

    public function calculatorDataProvider(): array
    {
        $fixtureOne = new \stdClass();
        $fixtureOne->str = 'test';
        $fixtureOne->emptyRelationArray = [];
        $fixtureOne->emptyIterator = new \ArrayIterator([]);
        $fixtureOne->relationArray = [1, 2];
        $fixtureOne->relationIterator = new \ArrayIterator([1, 2, 3]);

        $fixtureTwo = new \stdClass();
        $fixtureTwo->relationIterator = new \ArrayIterator([1, 2, 3, 4, 5]);

        return [
            [new \ArrayIterator([$fixtureOne]), 'str', 1],
            [new \ArrayIterator([$fixtureOne]), 'emptyRelationArray', 1],
            [new \ArrayIterator([$fixtureOne]), 'emptyIterator', 1],
            [new \ArrayIterator([$fixtureOne]), 'relationArray', 2],
            [new \ArrayIterator([$fixtureOne, $fixtureTwo]), 'relationIterator', 5],
        ];
    }
}
