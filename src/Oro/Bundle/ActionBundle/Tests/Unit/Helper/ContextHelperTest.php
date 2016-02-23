<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionTranslates;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ContextHelperTest extends \PHPUnit_Framework_TestCase
{
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

        $this->helper = new ContextHelper($this->doctrineHelper, $this->requestStack);
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
     */
    public function testGetContext($request, array $expected)
    {
        $this->requestStack->expects($this->exactly(4))
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
                ]
            ],
            [
                'request' => new Request(),
                'expected' => [
                    'route' => null,
                    'entityId' => null,
                    'entityClass' => null,
                    'datagrid' => null,
                ]
            ],
            [
                'request' => new Request(
                    [
                        'route' => 'test_route',
                        'entityId' => '42',
                        'entityClass' => 'stdClass',
                        'datagrid' => 'test_datagrid',
                    ]
                ),
                'expected' => [
                    'route' => 'test_route',
                    'entityId' => '42',
                    'entityClass' => 'stdClass',
                    'datagrid' => 'test_datagrid',
                ]
            ]
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
                'requestStackCalls' => 4,
                'expected' => new ActionData([
                    'data' => null,
                    'context' => ['route' => null, 'entityId' => null, 'entityClass' => null, 'datagrid' => null],
                    'translates' => new ActionTranslates()
                ])
            ],
            'empty request' => [
                'request' => new Request(),
                'requestStackCalls' => 4,
                'expected' => new ActionData([
                    'data' => null,
                    'context' => ['route' => null, 'entityId' => null, 'entityClass' => null, 'datagrid' => null],
                    'translates' => new ActionTranslates()
                ])
            ],
            'route1 without entity id' => [
                'request' => new Request(
                    [
                        'route' => 'test_route',
                        'entityClass' => 'stdClass'
                    ]
                ),
                'requestStackCalls' => 4,
                'expected' => new ActionData([
                    'data' => new \stdClass(),
                    'context' => [
                        'route' => 'test_route',
                        'entityId' => null,
                        'entityClass' => 'stdClass',
                        'datagrid' => null,
                    ],
                    'translates' => new ActionTranslates()
                ])
            ],
            'entity' => [
                'request' => new Request(),
                'requestStackCalls' => 0,
                'expected' => new ActionData([
                    'data' => $entity,
                    'context' => [
                        'route' => 'test_route',
                        'entityId' => '42',
                        'entityClass' => 'stdClass',
                        'datagrid' => null
                    ],
                    'translates' => new ActionTranslates()
                ]),
                'context' => [
                    'route' => 'test_route',
                    'entityId' => '42',
                    'entityClass' => 'stdClass'
                ]
            ],
            'entity (id as array)' => [
                'request' => new Request(),
                'requestStackCalls' => 0,
                'expected' => new ActionData([
                    'data' => $entity,
                    'context' => [
                        'route' => 'test_route',
                        'entityId' => ['params' => ['id' => '42']],
                        'entityClass' => 'stdClass',
                        'datagrid' => null
                    ],
                    'translates' => new ActionTranslates()
                ]),
                'context' => [
                    'route' => 'test_route',
                    'entityId' => ['params' => ['id' => '42']],
                    'entityClass' => 'stdClass'
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
            'entityId' => ['params' => ['id1' => '42', 'id2' => 100, 'id3' => 'test']]
        ];

        $context2 = [
            'entityId' => ['params' => ['id3' => 'test', 'id2' => '100', 'id1' => 42]],
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

        $actionData = new ActionData([
            'data' => $entity,
            'context' => array_merge($context1, [
                'datagrid' => null,
            ]),
            'translates' => new ActionTranslates(),
        ]);

        $this->assertEquals($actionData, $this->helper->getActionData($context1));

        // use local cache
        $this->assertEquals($actionData, $this->helper->getActionData($context2));
    }
}
