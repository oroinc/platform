<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData;

class TemplateDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $data
     *
     * @return TemplateData
     */
    private function getTemplateData(array $data): TemplateData
    {
        return new TemplateData($data, 'system', 'entity', 'computed');
    }

    public function testGetData()
    {
        $data = ['system' => ['key' => 'val'], 'entity' => new \stdClass()];
        $templateData = $this->getTemplateData($data);
        self::assertSame($data, $templateData->getData());
    }

    public function testHasSystemDataWhenItDoesNotExist()
    {
        $templateData = $this->getTemplateData([]);
        self::assertFalse($templateData->hasSystemData());
    }

    public function testHasSystemDataWhenItExists()
    {
        $templateData = $this->getTemplateData(['system' => ['key' => 'val']]);
        self::assertTrue($templateData->hasSystemData());
    }

    public function testGetSystemData()
    {
        $systemData = ['key' => 'val'];
        $templateData = $this->getTemplateData(['system' => $systemData]);
        self::assertSame($systemData, $templateData->getSystemData());
    }

    public function testHasEntityDataWhenItDoesNotExist()
    {
        $templateData = $this->getTemplateData([]);
        self::assertFalse($templateData->hasEntityData());
    }

    public function testHasEntityDataWhenItExists()
    {
        $templateData = $this->getTemplateData(['entity' => new \stdClass()]);
        self::assertTrue($templateData->hasEntityData());
    }

    public function testGetEntityData()
    {
        $entityData = new \stdClass();
        $templateData = $this->getTemplateData(['entity' => $entityData]);
        self::assertSame($entityData, $templateData->getEntityData());
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

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The computed variable "entity.field1" does not exist.
     */
    public function testGetComputedVariableWhenNoAnyComputedVariablesExist()
    {
        $templateData = $this->getTemplateData([]);
        $templateData->getComputedVariable('entity.field1');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The computed variable "entity.field1" does not exist.
     */
    public function testGetComputedVariableWhenItDoesNotExist()
    {
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

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The computed variable "entity.field1" must start with "computed.".
     */
    public function testGetVariablePathForNotComputedPath()
    {
        $templateData = $this->getTemplateData([]);
        $templateData->getVariablePath('entity.field1');
    }
}
