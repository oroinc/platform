<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityDataAccessor;
use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariableComputer;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TemplateDataTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityVariableComputer|\PHPUnit\Framework\MockObject\MockObject */
    private $entityVariableComputer;

    /** @var EntityDataAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $entityDataAccessor;

    protected function setUp(): void
    {
        $this->entityVariableComputer = $this->createMock(EntityVariableComputer::class);
        $this->entityDataAccessor = $this->createMock(EntityDataAccessor::class);
    }

    private function getTemplateData(array $data): TemplateData
    {
        return new TemplateData(
            $data,
            $this->entityVariableComputer,
            $this->entityDataAccessor,
            'system',
            'entity',
            'computed'
        );
    }

    public function testGetData()
    {
        $data = ['system' => ['key' => 'val'], 'entity' => new \stdClass()];
        $templateData = $this->getTemplateData($data);
        self::assertSame($data, $templateData->getData());
    }

    public function testHasSystemVariablesWhenTheyDoNotExist()
    {
        $templateData = $this->getTemplateData([]);
        self::assertFalse($templateData->hasSystemVariables());
    }

    public function testHasSystemVariablesWhenTheyExist()
    {
        $templateData = $this->getTemplateData(['system' => ['key' => 'val']]);
        self::assertTrue($templateData->hasSystemVariables());
    }

    public function testGetSystemVariablesWhenTheyDoNotExist()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This object does not contain values of system variables.');

        $templateData = $this->getTemplateData([]);
        $templateData->getSystemVariables();
    }

    public function testGetSystemVariablesTheyExist()
    {
        $systemVariables = ['key' => 'val'];
        $templateData = $this->getTemplateData(['system' => $systemVariables]);
        self::assertSame($systemVariables, $templateData->getSystemVariables());
    }

    public function testHasRootEntityWhenItDoesNotExist()
    {
        $templateData = $this->getTemplateData([]);
        self::assertFalse($templateData->hasRootEntity());
    }

    public function testHasRootEntityWhenItExists()
    {
        $templateData = $this->getTemplateData(['entity' => new \stdClass()]);
        self::assertTrue($templateData->hasRootEntity());
    }

    public function testGetRootEntityWhenItExists()
    {
        $entity = new \stdClass();
        $templateData = $this->getTemplateData(['entity' => $entity]);
        self::assertSame($entity, $templateData->getRootEntity());
    }

    public function testGetRootEntityWhenTheyDoNotExist()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This object does not contain the root entity.');

        $templateData = $this->getTemplateData([]);
        $templateData->getRootEntity();
    }

    public function testGetEntityVariableForNotRootEntity()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected "entity" variable, got "system".');

        $templateData = $this->getTemplateData([]);
        $templateData->getEntityVariable('system');
    }

    public function testGetEntityVariableForNotEntityRelatedVariablePath()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The variable "system.test" must start with "entity.".');

        $templateData = $this->getTemplateData([]);
        $templateData->getEntityVariable('system.test');
    }

    public function testGetEntityVariableForRootEntityWhenItDoesNotExist()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This object does not contain the root entity.');

        $templateData = $this->getTemplateData([]);
        $templateData->getEntityVariable('entity');
    }

    public function testGetEntityVariableForRootEntity()
    {
        $entity = new \stdClass();
        $templateData = $this->getTemplateData(['entity' => $entity]);
        self::assertSame($entity, $templateData->getEntityVariable('entity'));
    }

    public function testGetEntityVariableForFirstLevelChildEntity()
    {
        $entity = new \stdClass();
        $entity1 = new \stdClass();
        $templateData = $this->getTemplateData(['entity' => $entity]);
        $this->entityDataAccessor->expects(self::once())
            ->method('tryGetValue')
            ->with(self::identicalTo($entity), 'entity1')
            ->willReturnCallback(function ($parentValue, $propertyName, &$value) use ($entity1) {
                $value = $entity1;

                return true;
            });
        self::assertSame($entity1, $templateData->getEntityVariable('entity.entity1'));
    }

    public function testGetEntityVariableForFirstLevelChildEntityWhenItCannotBeResolved()
    {
        $entity = new \stdClass();
        $templateData = $this->getTemplateData(['entity' => $entity]);
        $this->entityDataAccessor->expects(self::once())
            ->method('tryGetValue')
            ->with(self::identicalTo($entity), 'entity1')
            ->willReturn(false);
        self::assertNull($templateData->getEntityVariable('entity.entity1'));
    }

    public function testGetEntityVariableForComputedFirstLevelChildEntity()
    {
        $entity = new \stdClass();
        $entity1 = new \stdClass();
        $templateData = $this->getTemplateData(['entity' => $entity]);
        $templateData->setComputedVariable('entity.entity1', $entity1);
        $this->entityDataAccessor->expects(self::never())
            ->method('tryGetValue');
        self::assertSame($entity1, $templateData->getEntityVariable('entity.entity1'));
    }

    public function testGetEntityVariableForSecondLevelChildEntity()
    {
        $entity = new \stdClass();
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();
        $templateData = $this->getTemplateData(['entity' => $entity]);
        $this->entityDataAccessor->expects(self::exactly(2))
            ->method('tryGetValue')
            ->withConsecutive(
                [self::identicalTo($entity), 'entity1'],
                [self::identicalTo($entity1), 'entity2']
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function ($parentValue, $propertyName, &$value) use ($entity1) {
                    $value = $entity1;

                    return true;
                }),
                new ReturnCallback(function ($parentValue, $propertyName, &$value) use ($entity2) {
                    $value = $entity2;

                    return true;
                })
            );

        self::assertSame($entity2, $templateData->getEntityVariable('entity.entity1.entity2'));
    }

    public function testGetEntityVariableForSecondLevelChildEntityWhenFirstLevelChildEntityCannotBeResolved()
    {
        $entity = new \stdClass();
        $templateData = $this->getTemplateData(['entity' => $entity]);
        $this->entityDataAccessor->expects(self::once())
            ->method('tryGetValue')
            ->with(self::identicalTo($entity), 'entity1')
            ->willReturn(false);
        self::assertNull($templateData->getEntityVariable('entity.entity1.entity2'));
    }

    public function testGetEntityVariableForSecondLevelChildEntityWhenItCannotBeResolved()
    {
        $entity = new \stdClass();
        $entity1 = new \stdClass();
        $templateData = $this->getTemplateData(['entity' => $entity]);
        $this->entityDataAccessor->expects(self::exactly(2))
            ->method('tryGetValue')
            ->withConsecutive(
                [self::identicalTo($entity), 'entity1'],
                [self::identicalTo($entity1), 'entity2']
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function ($parentValue, $propertyName, &$value) use ($entity1) {
                    $value = $entity1;

                    return true;
                }),
                false
            );

        self::assertNull($templateData->getEntityVariable('entity.entity1.entity2'));
    }

    public function testGetEntityVariableForSecondLevelChildEntityWhenFirstLevelEntityIsComputedOne()
    {
        $entity = new \stdClass();
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();
        $templateData = $this->getTemplateData(['entity' => $entity]);
        $templateData->setComputedVariable('entity.entity1', $entity1);
        $this->entityDataAccessor->expects(self::once())
            ->method('tryGetValue')
            ->with(self::identicalTo($entity1), 'entity2')
            ->willReturnCallback(function ($parentValue, $propertyName, &$value) use ($entity2) {
                $value = $entity2;

                return true;
            });
        self::assertSame($entity2, $templateData->getEntityVariable('entity.entity1.entity2'));
    }

    public function testGetParentVariablePathForInvalidVariable()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The variable "entity" must have at least 2 elements delimited by ".".');

        $templateData = $this->getTemplateData([]);
        $templateData->getParentVariablePath('entity');
    }

    public function testGetParentVariablePath()
    {
        $templateData = $this->getTemplateData([]);
        self::assertEquals('entity', $templateData->getParentVariablePath('entity.field'));
    }

    public function testHasComputedVariableWhenNoAnyComputedVariablesExist()
    {
        $templateData = $this->getTemplateData([]);
        self::assertFalse($templateData->hasComputedVariable('entity.field1'));
    }

    public function testHasComputedVariableWhenItDoesNotExist()
    {
        $data = [
            'computed' => [
                'entity__field2' => 'val1'
            ]
        ];
        $templateData = $this->getTemplateData($data);
        self::assertFalse($templateData->hasComputedVariable('entity.field1'));
    }

    public function testHasComputedVariableWhenItExists()
    {
        $data = [
            'computed' => [
                'entity__field1' => 'val1'
            ]
        ];
        $templateData = $this->getTemplateData($data);
        self::assertTrue($templateData->hasComputedVariable('entity.field1'));
    }

    public function testGetComputedVariableWhenNoAnyComputedVariablesExist()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The computed variable "entity.field1" does not exist.');

        $templateData = $this->getTemplateData([]);
        $templateData->getComputedVariable('entity.field1');
    }

    public function testGetComputedVariableWhenItDoesNotExist()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The computed variable "entity.field1" does not exist.');

        $data = [
            'computed' => [
                'entity__field2' => 'val1'
            ]
        ];
        $templateData = $this->getTemplateData($data);
        $templateData->getComputedVariable('entity.field1');
    }

    public function testGetComputedVariableWhenItExists()
    {
        $data = [
            'computed' => [
                'entity__field1' => 'val1'
            ]
        ];
        $templateData = $this->getTemplateData($data);
        self::assertSame('val1', $templateData->getComputedVariable('entity.field1'));
    }

    public function testHasAndGetComputedVariableWhenItExistsAndItsValueIsNull()
    {
        $data = [
            'computed' => [
                'entity__field1' => null
            ]
        ];
        $templateData = $this->getTemplateData($data);
        self::assertTrue($templateData->hasComputedVariable('entity.field1'));
        self::assertNull($templateData->getComputedVariable('entity.field1'));
    }

    public function testSetComputedVariable()
    {
        $templateData = $this->getTemplateData([]);

        $templateData->setComputedVariable('entity.field1', 'val1');
        self::assertTrue($templateData->hasComputedVariable('entity.field1'));
        self::assertSame('val1', $templateData->getComputedVariable('entity.field1'));

        $templateData->setComputedVariable('entity.association1.field1', 'val2');
        self::assertTrue($templateData->hasComputedVariable('entity.association1.field1'));
        self::assertSame('val2', $templateData->getComputedVariable('entity.association1.field1'));
        self::assertFalse($templateData->hasComputedVariable('entity.association1'));

        self::assertEquals(
            [
                'computed' => [
                    'entity__field1'               => 'val1',
                    'entity__association1__field1' => 'val2'
                ]
            ],
            $templateData->getData()
        );
    }

    public function testGetComputedVariablePath()
    {
        $templateData = $this->getTemplateData([]);
        self::assertSame('computed.entity', $templateData->getComputedVariablePath('entity'));
        self::assertSame('computed.entity__field1', $templateData->getComputedVariablePath('entity.field1'));
    }

    public function testGetVariablePath()
    {
        $templateData = $this->getTemplateData([]);
        self::assertSame('entity', $templateData->getVariablePath('computed.entity'));
        self::assertSame('entity.field1', $templateData->getVariablePath('computed.entity__field1'));
    }

    public function testGetVariablePathForNotComputedPath()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The computed variable "entity.field1" must start with "computed.".');

        $templateData = $this->getTemplateData([]);
        $templateData->getVariablePath('entity.field1');
    }
}
