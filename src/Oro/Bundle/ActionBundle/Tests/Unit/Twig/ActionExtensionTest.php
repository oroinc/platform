<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Twig;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Twig\ActionExtension;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ROUTE = 'test_route';
    const REQUEST_URI = '/test/request/uri';

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionManager */
    protected $actionManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ApplicationsHelper */
    protected $appsHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RequestStack */
    protected $requestStack;

    /** @var ActionExtension */
    protected $extension;

    protected function setUp()
    {
        $this->actionManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->appsHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ApplicationsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ActionExtension(
            $this->actionManager,
            $this->appsHelper,
            $this->doctrineHelper,
            $this->requestStack
        );
    }

    protected function tearDown()
    {
        unset($this->extension, $this->actionManager, $this->appsHelper, $this->doctrineHelper, $this->requestStack);
    }

    public function testGetName()
    {
        $this->assertEquals(ActionExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(3, $functions);

        $expectedFunctions = [
            'oro_action_widget_parameters' => true,
            'oro_action_widget_route' => false,
            'has_actions' => false,
        ];

        /** @var \Twig_SimpleFunction $function */
        foreach ($functions as $function) {
            $this->assertInstanceOf('\Twig_SimpleFunction', $function);
            $this->assertArrayHasKey($function->getName(), $expectedFunctions);
            $this->assertEquals($expectedFunctions[$function->getName()], $function->needsContext());
        }
    }

    /**
     * @dataProvider getWidgetParametersDataProvider
     *
     * @param array $context
     * @param array $expected
     */
    public function testGetWidgetParameters(array $context, array $expected)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Request $request */
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('get')
            ->with('_route')
            ->willReturn(self::ROUTE);
        $request->expects($this->once())
            ->method('getRequestUri')
            ->willReturn(self::REQUEST_URI);

        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn($request);

        if (array_key_exists('entity', $context)) {
            $this->doctrineHelper->expects($this->once())
                ->method('isNewEntity')
                ->with($context['entity'])
                ->willReturn(is_null($context['entity']->id));

            $this->doctrineHelper->expects($context['entity']->id ? $this->once() : $this->never())
                ->method('getEntityIdentifier')
                ->with($context['entity'])
                ->willReturn(['id' => $context['entity']->id]);
        }

        $this->assertEquals($expected, $this->extension->getWidgetParameters($context));
    }

    /**
     * @return array
     */
    public function getWidgetParametersDataProvider()
    {
        return [
            'empty context' => [
                'context' => [],
                'expected' => ['route' => self::ROUTE, 'fromUrl' => self::REQUEST_URI],
            ],
            'entity_class' => [
                'context' => ['entity_class' => '\stdClass'],
                'expected' => ['route' => self::ROUTE, 'entityClass' => '\stdClass', 'fromUrl' => self::REQUEST_URI],
            ],
            'new entity' => [
                'context' => ['entity' => $this->getEntity()],
                'expected' => ['route' => self::ROUTE, 'fromUrl' => self::REQUEST_URI],
            ],
            'existing entity' => [
                'context' => ['entity' => $this->getEntity(42)],
                'expected' => [
                    'route' => self::ROUTE,
                    'entityClass' => 'stdClass',
                    'entityId' => ['id' => 42],
                    'fromUrl' => self::REQUEST_URI
                ],
            ],
            'existing entity & entity_class' => [
                'context' => ['entity' => $this->getEntity(43), 'entity_class' => 'testClass'],
                'expected' => [
                    'route' => self::ROUTE,
                    'entityClass' => 'stdClass',
                    'entityId' => ['id' => 43],
                    'fromUrl' => self::REQUEST_URI
                ],
            ],
            'new entity & entity_class' => [
                'context' => ['entity' => $this->getEntity(), 'entity_class' => 'testClass'],
                'expected' => ['route' => self::ROUTE, 'entityClass' => 'testClass', 'fromUrl' => self::REQUEST_URI],
            ],
        ];
    }

    public function testGetWidgetRoute()
    {
        $this->appsHelper->expects($this->once())
            ->method('getWidgetRoute')
            ->withAnyParameters()
            ->willReturn('test_route');

        $this->assertSame('test_route', $this->extension->getWidgetRoute());
    }

    /**
     * @dataProvider hasActionsDataProvider
     *
     * @param bool $result
     */
    public function testHasActions($result)
    {
        $params = ['test_param' => 'test_param_value'];

        $this->actionManager->expects($this->once())
            ->method('hasActions')
            ->with($params)
            ->willReturn($result);

        $this->assertEquals($result, $this->extension->hasActions($params));
    }

    /**
     * @return array
     */
    public function hasActionsDataProvider()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @param int $id
     * @return \stdClass
     */
    protected function getEntity($id = null)
    {
        $entity = new \stdClass();
        $entity->id = $id;

        return $entity;
    }
}
