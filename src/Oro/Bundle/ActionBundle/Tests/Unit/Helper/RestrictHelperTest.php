<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Helper\RestrictHelper;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;

class RestrictHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var RestrictHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->helper = new RestrictHelper();
    }

    /**
     * @dataProvider restrictOperationsByGroupDataProvider
     */
    public function testRestrictOperationsByGroup(
        array $operationsValues,
        mixed $definedGroups,
        array $expectedOperations
    ) {
        /** @var Operation[] $operations */
        $operations = [];
        foreach ($operationsValues as $operationName => $buttonOptions) {
            $operation = $this->createMock(Operation::class);
            $operationDefinition = new OperationDefinition();
            $operationDefinition->setButtonOptions($buttonOptions);
            $operation->expects($this->any())
                ->method('getDefinition')
                ->willReturn($operationDefinition);
            $operations[$operationName] = $operation;
        }

        $restrictedOperations = $this->helper->restrictOperationsByGroup($operations, $definedGroups);
        foreach ($expectedOperations as $expectedOperationName) {
            $this->assertArrayHasKey($expectedOperationName, $operations);
            $this->assertArrayHasKey($expectedOperationName, $restrictedOperations);
            $this->assertEquals(
                spl_object_hash($operations[$expectedOperationName]),
                spl_object_hash($restrictedOperations[$expectedOperationName])
            );
        }
        foreach ($restrictedOperations as $operationName => $restrictedOperation) {
            $this->assertContains($operationName, $expectedOperations);
        }
    }

    public function restrictOperationsByGroupDataProvider(): array
    {
        return [
            'groupIsString' => [
                'operationsValues' => [
                    //operationName //button options
                    'operation0' => ['group' => null],
                    'operation2' => ['group' => 'group1'],
                    'operation3' => ['group' => 'group2'],
                    'operation4' => []
                ],
                'definedGroups' => 'group1',
                'expectedOperations' => ['operation2']
            ],
            'groupIsArray' => [
                'operationsValues' => [
                    'operation0' => ['group' => null],
                    'operation2' => ['group' => 'group1'],
                    'operation3' => ['group' => 'group2'],
                    'operation4' => []
                ],
                'definedGroups' => ['group1', 'group2'],
                'expectedOperations' => ['operation2', 'operation3']
            ],
            'groupIsFalse' => [
                'operationsValues' => [
                    'operation0' => ['group' => null],
                    'operation2' => ['group' => 'group1'],
                    'operation3' => ['group' => 'group2'],
                    'operation4' => []
                ],
                'definedGroups' => false,
                'expectedOperations' => ['operation4']
            ],
            'groupIsNull' => [
                'operationsValues' => [
                    'operation0' => ['group' => null],
                    'operation1' => ['group' => 'group1'],
                    'operation2' => []
                ],
                'definedGroups' => null,
                'expectedOperations' => ['operation0', 'operation1', 'operation2']
            ],
        ];
    }
}
