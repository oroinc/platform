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

        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionFactory $doctrineHelper */
        $actionFactory = $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ExpressionFactory $doctrineHelper */
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

    public function testGet()
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

        $group = $this->registry->get('action_group1');

        $this->assertEquals('action_group1', $group->getDefinition()->getName());
    }

    public function testGetException()
    {
        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([]);

        $this->setExpectedException(
            'Oro\Bundle\ActionBundle\Exception\ActionNotFoundException',
            'Action with name "not exists" not found'
        );

        $this->registry->get('not exists');
    }
}
