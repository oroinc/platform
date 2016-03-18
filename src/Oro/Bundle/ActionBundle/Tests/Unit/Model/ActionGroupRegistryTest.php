<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\ActionBundle\Model\Assembler\ActionGroupAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\ParameterAssembler;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory;

class ActionGroupRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigurationProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationProvider;

    /** @var ActionGroupAssembler */
    protected $assembler;

    /** @var ActionGroupRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->configurationProvider =
            $this->getMock('Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionFactory $actionFactory */
        $actionFactory = $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ExpressionFactory $conditionFactory */
        $conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assembler = new ActionGroupAssembler(
            $actionFactory,
            $conditionFactory,
            new ParameterAssembler(),
            $doctrineHelper
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
        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
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
