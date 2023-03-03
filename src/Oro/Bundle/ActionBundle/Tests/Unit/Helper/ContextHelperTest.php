<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContextHelperTest extends \PHPUnit\Framework\TestCase
{
    private const ROUTE = 'test_route';
    private const REQUEST_URI = '/test/request/uri';

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var ContextHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->helper = new ContextHelper(
            $this->doctrineHelper,
            PropertyAccess::createPropertyAccessor(),
            $this->requestStack
        );
    }

    /**
     * @dataProvider getContextDataProvider
     */
    public function testGetContext(?Request $request, array $expected, int $calls)
    {
        $this->requestStack->expects($this->exactly($calls))
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertEquals($expected, $this->helper->getContext());
    }

    public function getContextDataProvider(): array
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
     */
    public function testGetActionParameters(array $context, array $expected)
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with('_route')
            ->willReturn(self::ROUTE);
        $request->expects($this->once())
            ->method('getRequestUri')
            ->willReturn(self::REQUEST_URI);

        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
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
                ->willReturn(null === $context['entity']->id);

            $this->doctrineHelper->expects($context['entity']->id ? $this->once() : $this->never())
                ->method('getEntityIdentifier')
                ->with($context['entity'])
                ->willReturn(['id' => $context['entity']->id]);
        }

        $this->assertEquals($expected, $this->helper->getActionParameters($context));
    }

    public function testGetActionParametersException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Master Request is not defined');

        $this->helper->getActionParameters([]);
    }

    public function getActionParametersDataProvider(): array
    {
        return [
            'empty context' => [
                'context' => [],
                'expected' => ['route' => self::ROUTE, 'fromUrl' => self::REQUEST_URI],
            ],
            'entity_class' => [
                'context' => ['entity_class' => \stdClass::class],
                'expected' => [
                    'route' => self::ROUTE,
                    'entityClass' => \stdClass::class,
                    'fromUrl' => self::REQUEST_URI
                ],
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
     */
    public function testGetActionData(
        ?Request $request,
        int $requestStackCalls,
        ActionData $expected,
        array $context = null
    ) {
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

    public function getActionDataDataProvider(): array
    {
        $entity = new \stdClass();
        $entityClass = \stdClass::class;
        $entity->id = 42;

        return [
            'without request' => [
                'request' => null,
                'requestStackCalls' => 7,
                'expected' => new ActionData(
                    [
                        'data' => null,
                        ActionData::OPERATION_TOKEN =>  $this->generateOperationToken()
                    ]
                )
            ],
            'empty request' => [
                'request' => new Request(),
                'requestStackCalls' => 7,
                'expected' => new ActionData(
                    [
                        'data' => null,
                        ActionData::OPERATION_TOKEN =>  $this->generateOperationToken()
                    ]
                )
            ],
            'route1 without entity id' => [
                'request' => new Request(
                    [
                        'route' => 'test_route',
                        'entityClass' => $entityClass
                    ]
                ),
                'requestStackCalls' => 6,
                'expected' => new ActionData(
                    [
                        'data' => new \stdClass(),
                        ActionData::OPERATION_TOKEN =>  $this->generateOperationToken($entityClass)
                    ]
                )
            ],
            'entity' => [
                'request' => new Request(),
                'requestStackCalls' => 0,
                'expected' => new ActionData(
                    [
                        'data' => $entity,
                        ActionData::OPERATION_TOKEN =>  $this->generateOperationToken($entityClass, $entity->id)
                    ]
                ),
                'context' => [
                    'route' => 'test_route',
                    'entityId' => '42',
                    'entityClass' => 'stdClass'
                ]
            ],
            'entity (id as array)' => [
                'request' => new Request(),
                'requestStackCalls' => 0,
                'expected' => new ActionData(
                    [
                        'data' => $entity,
                        ActionData::OPERATION_TOKEN =>  $this->generateOperationToken(
                            $entityClass,
                            ['params' => ['id' => '42']]
                        )
                    ]
                ),
                'context' => [
                    'route' => 'test_route',
                    'entityId' => ['params' => ['id' => '42']],
                    'entityClass' => $entityClass
                ]
            ],
            'with datagrid' => [
                'request' => new Request(),
                'requestStackCalls' => 0,
                'expected' => new ActionData(
                    [
                        'data' => $entity,
                        'gridName' => 'test-grid-name',
                        ActionData::OPERATION_TOKEN =>  $this->generateOperationToken(
                            $entityClass,
                            $entity->id,
                            'test-grid-name'
                        )
                    ]
                ),
                'context' => [
                    'route' => 'test_route',
                    'entityId' => '42',
                    'entityClass' => $entityClass,
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

        $actionData = new ActionData(
            [
                'data' => $entity,
                ActionData::OPERATION_TOKEN =>  $this->generateOperationToken(
                    'stdClass',
                    array_merge($context1['entityId'], $context2['entityId'])
                )
            ]
        );

        $this->assertEquals($actionData, $this->helper->getActionData($context1));

        // use local cache
        $this->assertEquals($actionData, $this->helper->getActionData($context2));
    }

    private function getEntity(int $id = null): object
    {
        $entity = new \stdClass();
        $entity->id = $id;

        return $entity;
    }

    private function generateOperationToken(
        string $entityClass = null,
        mixed $entityId = null,
        string $datagrid = null
    ): string {
        $array = [];

        $properties = [
            ContextHelper::DATAGRID_PARAM => $datagrid,
            ContextHelper::ENTITY_CLASS_PARAM => $entityClass,
            ContextHelper::ENTITY_ID_PARAM => $entityId,
        ];
        foreach ($properties as $key => $value) {
            $array[$key] = $value;
            if (is_array($array[$key])) {
                ksort($array[$key]);
            }
        }
        ksort($array);

        return md5(json_encode($array, JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR));
    }
}
