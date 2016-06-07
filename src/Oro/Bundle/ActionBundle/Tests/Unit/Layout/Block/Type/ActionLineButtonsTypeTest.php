<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Layout\Block\Type\ActionLineButtonsType;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

class ActionLineButtonsTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApplicationsHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $applicationHelper;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ActionLineButtonsType
     */
    protected $type;

    protected function setUp()
    {
        $this->applicationHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ApplicationsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ActionLineButtonsType($this->applicationHelper, $this->doctrineHelper);
    }

    public function testBuildView()
    {
        $actions = [];
        $this->applicationHelper->expects($this->once())
            ->method('getDialogRoute')
            ->will($this->returnValue('dialog'));

        $this->applicationHelper->expects($this->once())
            ->method('getExecutionRoute')
            ->will($this->returnValue('execution'));

        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);

        $entity = new \stdClass();
        $expected = [
            'entity' => $entity,
            'actions' => $actions,
            'dialogRoute' => 'dialog',
            'executionRoute' => 'execution',
            'attr' => [
                'data-page-component-module' => 'oroaction/js/app/components/buttons-component'
            ]
        ];
        $resolvedOptions = $resolver->resolve(['entity' => $entity, 'actions' => $actions]);
        $this->assertEquals($expected, $resolvedOptions);

        $view = new BlockView();
        /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->will($this->returnValue(1));

        $this->type->buildView($view, $block, $resolvedOptions);

        $expectedViewVars = [
            'dialogRoute' => $resolvedOptions['dialogRoute'],
            'executionRoute' => $resolvedOptions['executionRoute'],
            'attr' => $resolvedOptions['attr'],
            'actions' => $actions,
            'entityClass' => get_class($entity),
            'entityId' => 1
        ];
        $this->assertEquals($expectedViewVars, $view->vars);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage entity or entityClass must be provided
     */
    public function testBuildViewException()
    {
        $view = new BlockView();
        /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $this->type->buildView($view, $block, []);
    }

    public function testBuildViewInvisible()
    {
        $view = new BlockView();
        /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $this->type->buildView($view, $block, ['visible' => false]);
    }
}
