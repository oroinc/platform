<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\ActionBundle\Model\ArgumentAssembler;
use Oro\Bundle\ActionBundle\Model\ActionGroupAssembler;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionGroupRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActionConfigurationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationProvider;

    /** @var ActionGroupAssembler */
    protected $assembler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionFactory */
    protected $actionFactory;

    /** @var ConditionFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $conditionFactory;

    /** @var ActionGroupRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->configurationProvider = $this
            ->getMockBuilder('Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionFactory = $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assembler = new ActionGroupAssembler(
            $this->actionFactory,
            $this->conditionFactory,
            new ArgumentAssembler()
        );

        $this->registry = new ActionGroupRegistry(
            $this->configurationProvider,
            $this->assembler
        );
    }

    /**
     * @dataProvider findByNameDataProvider
     *
     * @param string $actionGroupName
     * @param string|null $expected
     */
    public function testFindByName($actionGroupName, $expected)
    {
        $this->markTestIncomplete();
        $this->configurationProvider->expects($this->once())
            ->method('getActionGroupConfiguration')
            ->willReturn(
                [
                    'action_group1' => [
                        'label' => 'Label1'
                    ]
                ]
            );

        $actionGroup = $this->registry->findByName($actionGroupName);

        $this->assertEquals($expected, $actionGroup ? $actionGroup->getDefinition()->getName() : $actionGroup);
    }

    /**
     * @return array
     */
    public function findByNameDataProvider()
    {
        return [
            'invalid actionGroup name' => [
                'actionGroupName' => 'test',
                'expected' => null
            ],
            'valid actionGroup name' => [
                'actionGroupName' => 'action_group1',
                'expected' => 'action_group1'
            ],
        ];
    }
}
