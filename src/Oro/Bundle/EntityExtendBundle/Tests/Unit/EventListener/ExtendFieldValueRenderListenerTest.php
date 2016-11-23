<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\EntityExtendBundle\EventListener\ExtendFieldValueRenderListener;

class ExtendFieldValueRenderListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExtendFieldValueRenderListener
     */
    protected $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $extendProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $facade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityClassNameHelper;

    public function setUp()
    {
        $this->configManger = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManger->expects($this->once())
            ->method('getProvider')
            ->will($this->returnValue($this->extendProvider));
        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->facade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityClassNameHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->target = new ExtendFieldValueRenderListener(
            $this->configManger,
            $this->router,
            $this->registry,
            $this->facade,
            $this->entityClassNameHelper
        );
    }

    /**
     * @dataProvider collectionDataProvider
     *
     * @param array $data
     * @param array $expected
     */
    public function testBeforeValueRenderProceedCollection(array $data, array $expected)
    {
        $entity = $this->getMock('\StdClass');
        $value = $this->getCollectionValue($data['shownFields'], $data['entities']);

        $entityClass = 'Oro\Bundle\UserBundle\Entity\User';
        $classParam = 'Oro_Bundle_UserBundle_Entity_User';

        $fieldConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId')
            ->disableOriginalConstructor()
            ->getMock();

        $extendConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');

        $extendConfig->expects($this->exactly(2))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['target_title', false, null, $data['shownFields']],
                        ['target_entity', false, null, $entityClass],
                    ]
                )
            );

        $this->extendProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldConfig)
            ->will($this->returnValue($extendConfig));

        $this->entityClassNameHelper->expects($this->any())
            ->method('getUrlSafeClassName')
            ->willReturn($classParam);

        if (isset($data['viewPageRoute'])) {
            $this->setupEntityMetadataStub($data['viewPageRoute'], $entityClass);
            $this->setupRouterStub($data['viewPageRoute'], $data['entities'], $data['isCustomEntity'], $classParam);
        }

        $this->setupExtendRelationConfigStub($entityClass, $data['isCustomEntity'], true);

        $event = new ValueRenderEvent($entity, $value, $fieldConfig);
        $this->target->beforeValueRender($event);
        $value = $event->getFieldViewValue();

        $this->assertArrayHasKey('values', $value);

        $this->assertEquals($expected, $value['values']);
    }

    /**
     * @dataProvider relationsDataProvider
     *
     * @param array        $data
     * @param array|string $expected
     */
    public function testBeforeValueRenderProceedSingleRelations(array $data, $expected)
    {
        $entity = $this->getMock('\StdClass');
        $value = $this->getMock('\StdClass', ['getId']);
        $fieldType = 'manyToOne';

        if (!empty($data['field'])) {
            $value->{$data['field']} = $data['title'];
        }
        $value->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($data['id']));

        if (isset($data['permissionsGranted'])) {
            $this->facade->expects($this->once())
                ->method('isGranted')
                ->with('VIEW', $value)
                ->willReturn(true);
        }

        $fieldConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldConfig->expects($this->once())
            ->method('getFieldType')
            ->will($this->returnValue($fieldType));

        $this->setupManyToOneExtendConfigMock($data['field'], $data['class'], $fieldConfig);
        $this->setupManyToOneMetadataStub($data['class']);
        $this->setupExtendRelationConfigStub($data['class'], $data['isCustomEntity']);

        if (isset($data['viewPageRoute'])) {
            $this->setupEntityMetadataStub($data['viewPageRoute'], $data['class']);
            if ($data['routeClassParam']) {
                $this->entityClassNameHelper->expects($this->any())
                    ->method('getUrlSafeClassName')
                    ->willReturn($data['routeClassParam']);
            }
            $this->setupRouterStub($data['viewPageRoute'], [$data], $data['isCustomEntity'], $data['routeClassParam']);
        }

        $event = new ValueRenderEvent($entity, $value, $fieldConfig);
        $this->target->beforeValueRender($event);
        $actual = $event->getFieldViewValue();

        $this->assertEquals($expected, $actual);
    }

    public function collectionDataProvider()
    {
        return [
            'custom entities' => [
                'data'     => [
                    'viewPageRoute'  => ExtendFieldValueRenderListener::ENTITY_VIEW_ROUTE,
                    'isCustomEntity' => true,
                    'shownFields'    => ['FirstName', 'LastName'],
                    'entities'       => [
                        [
                            'shownFieldValues' => ['FirstName' => 'john', 'LastName' => 'Doe'],
                            'id'               => 42,
                            'url'              => '/test-route/42',
                            'permitted'        => true
                        ],
                        [
                            'shownFieldValues' => ['FirstName' => 'jack', 'LastName' => 'smith'],
                            'id'               => 84,
                            'permitted'        => false,
                        ]
                    ],
                ],
                'expected' => [
                    [
                        'id'    => 42,
                        'title' => 'john Doe',
                        'link'  => '/test-route/42',
                    ],
                    [
                        'id'    => 84,
                        'title' => 'jack smith',
                    ]
                ]
            ],
            'regular entities' => [
                'data'     => [
                    'viewPageRoute'  => 'test',
                    'isCustomEntity' => false,
                    'shownFields'    => ['FirstName', 'LastName'],
                    'entities'       => [
                        [
                            'shownFieldValues' => ['FirstName' => 'john', 'LastName' => 'Doe'],
                            'id'               => 42,
                            'url'              => '/test-route/42',
                            'permitted'        => true
                        ],
                        [
                            'shownFieldValues' => ['FirstName' => 'jack', 'LastName' => 'smith'],
                            'id'               => 84,
                            'permitted'        => false,
                        ]
                    ],
                ],
                'expected' => [
                    [
                        'id'    => 42,
                        'title' => 'john Doe',
                        'link'  => '/test-route/42',
                    ],
                    [
                        'id'    => 84,
                        'title' => 'jack smith',
                    ]
                ]
            ]
        ];
    }

    public function relationsDataProvider()
    {
        return [
            'entity class not exist' => [
                'data' => [
                    'isCustomEntity' => false,
                    'class' => null,
                    'id' => null,
                    'field' => null
                ],
                'expected' => ''
            ],
            'If Route Not Found will return text' => [
                'data' => [
                    'isCustomEntity' => false,
                    'class' => 'Oro\Bundle\UserBundle\Entity\User',
                    'id' => 42,
                    'field' => 'username',
                    'title' => 'test title'
                ],
                'expected' => 'test title'
            ],
            'entity class exists, route exists, permission granted' => [
                'data' => [
                    'viewPageRoute'  => 'test',
                    'isCustomEntity' => false,
                    'id' => 54,
                    'url' => "/test-route/54",
                    'class' => 'Oro\Bundle\UserBundle\Entity\User',
                    'routeClassParam' => null,
                    'field' => 'username',
                    'title' => 'test title',
                    'permissionsGranted' => true
                ],
                'expected' => [
                    'link' => "/test-route/54",
                    'title' => 'test title'
                ]
            ],
            'entity class exists, route exist, but permission not granted' => [
                'data' => [
                    'viewPageRoute'  => 'test',
                    'isCustomEntity' => false,
                    'id' => 54,
                    'url' => "/test-route/54",
                    'class' => 'Oro\Bundle\UserBundle\Entity\User',
                    'routeClassParam' => null,
                    'field' => 'username',
                    'title' => 'test title'
                ],
                'expected' => 'test title'
            ],
            'entity class extend, permission granted' => [
                'data' => [
                    'viewPageRoute'  => ExtendFieldValueRenderListener::ENTITY_VIEW_ROUTE,
                    'isCustomEntity' => true,
                    'id' => 22,
                    'url' => "/test-route/22",
                    'class' => 'Oro\Bundle\UserBundle\Entity\User',
                    'routeClassParam' => 'Oro_Bundle_UserBundle_Entity_User',
                    'field' => 'username',
                    'title' => 'test title',
                    'permissionsGranted' => true
                ],
                'expected' => [
                    'link' => "/test-route/22",
                    'title' => 'test title'
                ]
            ],
        ];
    }

    /**
     * @param array $shownFields
     * @param array $relatedEntitiesList
     *
     * @return ArrayCollection
     */
    protected function getCollectionValue(array $shownFields, array $relatedEntitiesList)
    {
        $value = new ArrayCollection();
        $grantedEntitiesMap = [];

        foreach ($relatedEntitiesList as $entity) {
            $entityMethods = ['getId'];
            foreach ($shownFields as $field) {
                $entityMethods[] = "get{$field}";
            }
            $item = $this->getMock('\StdClass', $entityMethods);

            foreach ($shownFields as $field) {
                $item->expects($this->once())
                    ->method("get{$field}")
                    ->will($this->returnValue($entity['shownFieldValues'][$field]));
            }

            if ($entity['permitted']) {
                $grantedEntitiesMap[] = ['VIEW', $item, true];
            }
            $item->expects($this->any())
                ->method('getId')
                ->will($this->returnValue($entity['id']));
            $value->add($item);
        }
        $this->facade->expects($this->any())
            ->method('isGranted')
            ->willReturnMap($grantedEntitiesMap);


        return $value;
    }

    /**
     * @param string $expectedClass
     * @param bool   $isCustomEntity
     * @param bool   $expectGet
     */
    protected function setupExtendRelationConfigStub($expectedClass, $isCustomEntity = true, $expectGet = false)
    {
        $relationExtendConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        if ($expectGet) {
            $relationExtendConfig
                ->expects($this->once())
                ->method('get')
                ->with('pk_columns', false, ['id'])
                ->willReturn(['id']);
        }

        $relationExtendConfig->expects($this->any())
            ->method('is')
            ->willReturnMap([
                ['owner', ExtendScope::OWNER_CUSTOM, $isCustomEntity]
            ]);
        $this->extendProvider->expects($this->any())
            ->method('getConfig')
            ->with($expectedClass)
            ->will($this->returnValue($relationExtendConfig));
    }

    /**
     * @param string        $field
     * @param string        $expectedClass
     * @param FieldConfigId $fieldConfig
     */
    protected function setupManyToOneExtendConfigMock($field, $expectedClass, FieldConfigId $fieldConfig)
    {
        $extendConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $extendConfig->expects($this->exactly(2))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['target_field', false, null, $field],
                        ['target_entity', false, null, $expectedClass],
                    ]
                )
            );
        $this->extendProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldConfig)
            ->will($this->returnValue($extendConfig));
    }

    /**
     * @param string $expectedClass
     */
    protected function setupManyToOneMetadataStub($expectedClass)
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($expectedClass)
            ->will($this->returnValue($metadata));
    }

    /**
     * @param string $routeName
     * @param string $class
     */
    protected function setupEntityMetadataStub($routeName, $class)
    {
        $metadata = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->routeView = $routeName;
        $this->configManger->expects($this->any())
            ->method('getEntityMetadata')
            ->with($class)
            ->will($this->returnValue($metadata));
    }

    /**
     * @param string $routeName
     * @param array  $entities
     * @param bool   $isCustomEntity
     * @param string $entityNameParam
     */
    protected function setupRouterStub($routeName, array $entities, $isCustomEntity = false, $entityNameParam = '')
    {
        $map = [];
        foreach ($entities as $expectedValue) {
            if (isset($expectedValue['url'])) {
                $routeParams = [];
                if ($isCustomEntity) {
                    $routeParams['entityName'] = $entityNameParam;
                }
                $routeParams['id'] = $expectedValue['id'];

                $map[] = [$routeName, $routeParams, UrlGeneratorInterface::ABSOLUTE_PATH, $expectedValue['url']];
            }
        }
        $this->router->expects($this->any())
            ->method('generate')
            ->willReturnMap($map);
    }
}
