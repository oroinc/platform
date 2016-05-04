<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ContextHelperTest extends \PHPUnit_Framework_TestCase
{
    const ROUTE = 'test_route';
    const REQUEST_URI = '/test/request/uri';

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RequestStack */
    protected $requestStack;

    /** @var ContextHelper */
    protected $helper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
            ->disableOriginalConstructor()
            ->getMock();

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->helper = new ContextHelper($this->doctrineHelper, $propertyAccessor, $this->requestStack);
    }

    protected function tearDown()
    {
        unset($this->helper, $this->doctrineHelper, $this->requestStack);
    }

    /**
     * @dataProvider getContextDataProvider
     *
     * @param Request|null $request
     * @param array $expected
     * @param int $calls
     */
    public function testGetContext($request, array $expected, $calls)
    {
        $this->requestStack->expects($this->exactly($calls))
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertEquals($expected, $this->helper->getContext());
    }

    /**
     * @return array
     */
    public function getContextDataProvider()
    {
        return [
            [
                'request' => null,
                'expected' => [
                    'route' => null,
                    'entityId' => null,
                    'entityClass' => null,
                    'datagrid' => null,
                    'group' => null,
                    'fromUrl' => null,
                ],
                'calls' => 7,
            ],
            [
                'request' => new Request(),
                'expected' => [
                    'route' => null,
                    'entityId' => null,
                    'entityClass' => null,
                    'datagrid' => null,
                    'group' => null,
                    'fromUrl' => null,
                ],
                'calls' => 7,
            ],
            [
                'request' => new Request(
                    [
                        'route' => 'test_route',
                        'entityId' => '42',
                        'entityClass' => 'stdClass',
                        'datagrid' => 'test_datagrid',
                        'group' => 'test_group',
                        'fromUrl' => 'test-url',
                    ]
                ),
                'expected' => [
                    'route' => 'test_route',
                    'entityId' => '42',
                    'entityClass' => 'stdClass',
                    'datagrid' => 'test_datagrid',
                    'group' => 'test_group',
                    'fromUrl' => 'test-url'
                ],
                'calls' => 6,
            ]
        ];
    }

    /**
     * @dataProvider getActionParametersDataProvider
     *
     * @param array $context
     * @param array $expected
     */
    public function testGetActionParameters(array $context, array $expected)
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
            $this->doctrineHelper->expects($this->any())
                ->method('isManageableEntity')
                ->withAnyParameters()
                ->willReturnCallback(function ($entity) {
                    return $entity instanceof \stdClass;
                });
            $this->doctrineHelper->expects($this->any())
                ->method('isNewEntity')
                ->with($context['entity'])
                ->willReturn(is_null($context['entity']->id));

            $this->doctrineHelper->expects($context['entity']->id ? $this->once() : $this->never())
                ->method('getEntityIdentifier')
                ->with($context['entity'])
                ->willReturn(['id' => $context['entity']->id]);
        }

        $this->assertEquals($expected, $this->helper->getActionParameters($context));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Master Request is not defined
     */
    public function testGetActionParametersException()
    {
        $this->helper->getActionParameters([]);
    }

    /**
     * @return array
     */
    public function getActionParametersDataProvider()
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

    /**
     * @dataProvider getActionDataDataProvider
     *
     * @param Request|null $request
     * @param int $requestStackCalls
     * @param ActionData $expected
     * @param array $context
     */
    public function testGetActionData($request, $requestStackCalls, ActionData $expected, array $context = null)
    {
        $entity = new \stdClass();
        $entity->id = 42;

        $this->requestStack->expects($this->exactly($requestStackCalls * 2))
            ->method('getCurrentRequest')
            ->willReturn($request);

        if ($expected->getEntity()) {
            $this->doctrineHelper->expects($this->once())
                ->method('isManageableEntity')
                ->with('stdClass')
                ->willReturn(true);

            if ($request->get('entityId') || ($expected->getEntity() && isset($expected->getEntity()->id))) {
                $this->doctrineHelper->expects($this->once())
                    ->method('getEntityReference')
                    ->with('stdClass', $this->logicalOr(42, $this->isType('array')))
                    ->willReturn($entity);
            } else {
                $this->doctrineHelper->expects($this->once())
                    ->method('createEntityInstance')
                    ->with('stdClass')
                    ->willReturn(new \stdClass());
            }
        }

        $this->assertEquals($expected, $this->helper->getActionData($context));

        // use local cache
        $this->assertEquals($expected, $this->helper->getActionData($context));
    }

    /**
     * @return array
     */
    public function getActionDataDataProvider()
    {
        $entity = new \stdClass();
        $entity->id = 42;

        return [
            'without request' => [
                'request' => null,
                'requestStackCalls' => 7,
                'expected' => new ActionData(['data' => null])
            ],
            'empty request' => [
                'request' => new Request(),
                'requestStackCalls' => 7,
                'expected' => new ActionData(['data' => null])
            ],
            'route1 without entity id' => [
                'request' => new Request(
                    [
                        'route' => 'test_route',
                        'entityClass' => 'stdClass'
                    ]
                ),
                'requestStackCalls' => 6,
                'expected' => new ActionData(['data' => new \stdClass()])
            ],
            'entity' => [
                'request' => new Request(),
                'requestStackCalls' => 0,
                'expected' => new ActionData(['data' => $entity]),
                'context' => [
                    'route' => 'test_route',
                    'entityId' => '42',
                    'entityClass' => 'stdClass'
                ]
            ],
            'entity (id as array)' => [
                'request' => new Request(),
                'requestStackCalls' => 0,
                'expected' => new ActionData(['data' => $entity]),
                'context' => [
                    'route' => 'test_route',
                    'entityId' => ['params' => ['id' => '42']],
                    'entityClass' => 'stdClass'
                ]
            ],
            'with datagrid' => [
                'request' => new Request(),
                'requestStackCalls' => 0,
                'expected' => new ActionData(['data' => $entity, 'gridName' => 'test-grid-name']),
                'context' => [
                    'route' => 'test_route',
                    'entityId' => '42',
                    'entityClass' => 'stdClass',
                    'datagrid' => 'test-grid-name'
                ]
            ]
        ];
    }

    public function testGetActionDataWithCache()
    {
        $entity = new \stdClass();
        $entity->id1 = 42;
        $entity->id2 = 100;
        $entity->id3 = 'test';

        $context1 = [
            'route' => 'test_route',
            'entityClass' => 'stdClass',
            'entityId' => ['id1' => '42', 'id2' => 100, 'id3' => 'test']
        ];

        $context2 = [
            'entityId' => ['id3' => 'test', 'id2' => '100', 'id1' => 42],
            'route' => 'test_route',
            'extra_parameter' => new \stdClass(),
            'entityClass' => 'stdClass'
        ];

        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->with('stdClass')
            ->willReturn(true);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturn($entity);

        $actionData = new ActionData(['data' => $entity]);

        $this->assertEquals($actionData, $this->helper->getActionData($context1));

        // use local cache
        $this->assertEquals($actionData, $this->helper->getActionData($context2));
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
