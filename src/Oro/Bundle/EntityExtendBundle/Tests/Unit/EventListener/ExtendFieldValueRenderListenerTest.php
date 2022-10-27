<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\EntityExtendBundle\EventListener\ExtendFieldValueRenderListener;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ExtendFieldValueRenderListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManger;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var EntityClassNameHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassNameHelper;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendProvider;

    /** @var ExtendFieldValueRenderListener */
    private $listener;

    protected function setUp(): void
    {
        $this->configManger = $this->createMock(ConfigManager::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->entityClassNameHelper = $this->createMock(EntityClassNameHelper::class);
        $this->extendProvider = $this->createMock(ConfigProvider::class);

        $this->configManger->expects($this->once())
            ->method('getProvider')
            ->willReturn($this->extendProvider);

        $this->listener = new ExtendFieldValueRenderListener(
            $this->configManger,
            $this->router,
            $this->registry,
            $this->authorizationChecker,
            $this->entityClassNameHelper
        );
    }

    /**
     * @dataProvider collectionDataProvider
     */
    public function testBeforeValueRenderProceedCollection(array $data, array $expected)
    {
        $entity = $this->createMock(\stdClass::class);
        $value = $this->getCollectionValue($data['shownFields'], $data['entities']);

        $entityClass = User::class;
        $classParam = 'Oro_Bundle_UserBundle_Entity_User';

        $fieldConfig = $this->createMock(FieldConfigId::class);

        $extendConfig = $this->createMock(ConfigInterface::class);

        $extendConfig->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['target_title', false, null, $data['shownFields']],
                ['target_entity', false, null, $entityClass],
            ]);

        $this->extendProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldConfig)
            ->willReturn($extendConfig);

        $this->entityClassNameHelper->expects($this->any())
            ->method('getUrlSafeClassName')
            ->willReturn($classParam);

        if (isset($data['viewPageRoute'])) {
            $this->setupEntityMetadataStub($data['viewPageRoute'], $entityClass);
            $this->setupRouterStub($data['viewPageRoute'], $data['entities'], $data['isCustomEntity'], $classParam);
        }

        $this->setupExtendRelationConfigStub($entityClass, $data['isCustomEntity'], true);

        $event = new ValueRenderEvent($entity, $value, $fieldConfig);
        $this->listener->beforeValueRender($event);
        $value = $event->getFieldViewValue();

        $this->assertArrayHasKey('values', $value);

        $this->assertEquals($expected, $value['values']);
    }

    /**
     * @dataProvider relationsDataProvider
     */
    public function testBeforeValueRenderProceedSingleRelations(array $data, array $expected)
    {
        $entity = $this->createMock(\stdClass::class);
        $value = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $fieldType = 'manyToOne';

        if (!empty($data['field'])) {
            $value->{$data['field']} = $data['title'];
        }
        $value->expects($this->any())
            ->method('getId')
            ->willReturn($data['id']);

        if (isset($data['permissionsGranted'])) {
            $this->authorizationChecker->expects($this->once())
                ->method('isGranted')
                ->with('VIEW', $value)
                ->willReturn(true);
        }

        $fieldConfig = $this->createMock(FieldConfigId::class);
        $fieldConfig->expects($this->once())
            ->method('getFieldType')
            ->willReturn($fieldType);

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
        $this->listener->beforeValueRender($event);
        $actual = $event->getFieldViewValue();

        $this->assertEquals($expected, $actual);
    }

    public function collectionDataProvider(): array
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

    public function relationsDataProvider(): array
    {
        return [
            'entity class not exist' => [
                'data' => [
                    'isCustomEntity' => false,
                    'class' => null,
                    'id' => null,
                    'field' => null
                ],
                'expected' => ['title' => '']
            ],
            'If Route Not Found will return text' => [
                'data' => [
                    'isCustomEntity' => false,
                    'class' => User::class,
                    'id' => 42,
                    'field' => 'username',
                    'title' => 'test title'
                ],
                'expected' => ['title' => 'test title']
            ],
            'entity class exists, route exists, permission granted' => [
                'data' => [
                    'viewPageRoute'  => 'test',
                    'isCustomEntity' => false,
                    'id' => 54,
                    'url' => '/test-route/54',
                    'class' => User::class,
                    'routeClassParam' => null,
                    'field' => 'username',
                    'title' => 'test title',
                    'permissionsGranted' => true
                ],
                'expected' => [
                    'link' => '/test-route/54',
                    'title' => 'test title'
                ]
            ],
            'entity class exists, route exist, but permission not granted' => [
                'data' => [
                    'viewPageRoute'  => 'test',
                    'isCustomEntity' => false,
                    'id' => 54,
                    'url' => '/test-route/54',
                    'class' => User::class,
                    'routeClassParam' => null,
                    'field' => 'username',
                    'title' => 'test title'
                ],
                'expected' => ['title' => 'test title']
            ],
            'entity class extend, permission granted' => [
                'data' => [
                    'viewPageRoute'  => ExtendFieldValueRenderListener::ENTITY_VIEW_ROUTE,
                    'isCustomEntity' => true,
                    'id' => 22,
                    'url' => '/test-route/22',
                    'class' => User::class,
                    'routeClassParam' => 'Oro_Bundle_UserBundle_Entity_User',
                    'field' => 'username',
                    'title' => 'test title',
                    'permissionsGranted' => true
                ],
                'expected' => [
                    'link' => '/test-route/22',
                    'title' => 'test title'
                ]
            ],
        ];
    }

    private function getCollectionValue(array $shownFields, array $relatedEntitiesList): ArrayCollection
    {
        $value = new ArrayCollection();
        $grantedEntitiesMap = [];

        foreach ($relatedEntitiesList as $entity) {
            $entityMethods = ['getId'];
            foreach ($shownFields as $field) {
                $entityMethods[] = "get{$field}";
            }
            $item = $this->getMockBuilder(\stdClass::class)
                ->addMethods($entityMethods)
                ->getMock();

            foreach ($shownFields as $field) {
                $item->expects($this->once())
                    ->method('get' . $field)
                    ->willReturn($entity['shownFieldValues'][$field]);
            }

            if ($entity['permitted']) {
                $grantedEntitiesMap[] = ['VIEW', $item, true];
            }
            $item->expects($this->any())
                ->method('getId')
                ->willReturn($entity['id']);
            $value->add($item);
        }
        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnMap($grantedEntitiesMap);

        return $value;
    }

    private function setupExtendRelationConfigStub(
        ?string $expectedClass,
        bool $isCustomEntity = true,
        bool $expectGet = false
    ): void {
        $relationExtendConfig = $this->createMock(ConfigInterface::class);
        if ($expectGet) {
            $relationExtendConfig->expects($this->once())
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
            ->willReturn($relationExtendConfig);
    }

    private function setupManyToOneExtendConfigMock(
        ?string $field,
        ?string $expectedClass,
        FieldConfigId $fieldConfig
    ): void {
        $extendConfig = $this->createMock(ConfigInterface::class);
        $extendConfig->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['target_field', false, null, $field],
                ['target_entity', false, null, $expectedClass],
            ]);
        $this->extendProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldConfig)
            ->willReturn($extendConfig);
    }

    private function setupManyToOneMetadataStub(?string $expectedClass): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $entityManager = $this->createMock(EntityManager::class);
        $this->registry->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($expectedClass)
            ->willReturn($metadata);
    }

    private function setupEntityMetadataStub(string $routeName, string $class): void
    {
        $metadata = new EntityMetadata($class);
        $metadata->routeView = $routeName;
        $this->configManger->expects($this->any())
            ->method('getEntityMetadata')
            ->with($class)
            ->willReturn($metadata);
    }

    private function setupRouterStub(
        string $routeName,
        array $entities,
        bool $isCustomEntity = false,
        ?string $entityNameParam = ''
    ): void {
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
