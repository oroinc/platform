<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Event\LoadFieldsMetadata;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\Label;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntitySecurityMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheItem;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var AclGroupProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $aclGroupProvider;

    /** @var EntitySecurityMetadataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->aclGroupProvider = $this->createMock(AclGroupProviderInterface::class);

        $this->provider = new EntitySecurityMetadataProvider(
            $this->configManager,
            $this->doctrine,
            $this->cache,
            $this->eventDispatcher,
            $this->aclGroupProvider
        );
    }

    private function getEntitySecurityMetadata(): EntitySecurityMetadata
    {
        return new EntitySecurityMetadata(
            EntitySecurityMetadataProvider::ACL_SECURITY_TYPE,
            \stdClass::class,
            'SomeGroup',
            new Label('entity.label'),
            [],
            null,
            '',
            [
                'cityName'  => new FieldSecurityMetadata(
                    'cityName',
                    new Label('stdclass.city_name.label'),
                    []
                ),
                'firstName' => new FieldSecurityMetadata(
                    'firstName',
                    new Label('stdclass.first_name.label'),
                    ['VIEW', 'CREATE']
                ),
                'lastName'  => new FieldSecurityMetadata(
                    'lastName',
                    new Label('stdclass.last_name.label'),
                    [],
                    null,
                    'lastNameAlias'
                )
            ]
        );
    }

    private function getEntityConfig(string $scope, string $entityClass, array $values = []): Config
    {
        return new Config(
            new EntityConfigId($scope, $entityClass),
            $values
        );
    }

    private function getFieldConfig(string $scope, string $entityClass, string $fieldName, array $values = []): Config
    {
        return new Config(
            new FieldConfigId($scope, $entityClass, $fieldName, 'string'),
            $values
        );
    }

    private function expectClassMetadata(string $entityClass): void
    {
        $metadata = new ClassMetadata($entityClass);
        $metadata->identifier = ['id'];

        $manager = $this->createMock(EntityManagerInterface::class);
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->willReturn($metadata);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);
    }

    private function expectLoadMetadata(CacheItemInterface $cacheItem): EntitySecurityMetadata
    {
        $entitySecurityMetadata = $this->getEntitySecurityMetadata();

        $securityConfig = $this->getEntityConfig(
            'security',
            \stdClass::class,
            [
                'type'                => EntitySecurityMetadataProvider::ACL_SECURITY_TYPE,
                'permissions'         => 'All',
                'group_name'          => 'SomeGroup',
                'category'            => '',
                'field_acl_supported' => true,
                'field_acl_enabled'   => true
            ]
        );
        $entityConfig = $this->getEntityConfig('entity', \stdClass::class, ['label' => 'entity.label']);
        $extendConfig = $this->getEntityConfig('extend', \stdClass::class, ['state' => ExtendScope::STATE_ACTIVE]);
        $fieldsConfigs = [
            $this->getFieldConfig('security', \stdClass::class, 'id'),
            $this->getFieldConfig('security', \stdClass::class, 'firstName', ['permissions' => 'VIEW;CREATE']),
            $this->getFieldConfig('security', \stdClass::class, 'lastName', ['permissions' => 'All']),
            $this->getFieldConfig('security', \stdClass::class, 'cityName')
        ];

        $this->configManager->expects($this->any())
            ->method('getConfigs')
            ->willReturnMap([
                ['security', null, true, [$securityConfig]],
                ['security', \stdClass::class, false, $fieldsConfigs]
            ]);
        $this->configManager->expects($this->any())
            ->method('getEntityConfig')
            ->willReturnMap([
                ['entity', \stdClass::class, $entityConfig],
                ['extend', \stdClass::class, $extendConfig]
            ]);

        $this->expectClassMetadata(\stdClass::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(LoadFieldsMetadata::class), LoadFieldsMetadata::NAME)
            ->willReturnCallback(function (LoadFieldsMetadata $event) {
                $fields = $event->getFields();
                $lastNameField = $fields['lastName'];
                $fields['lastName'] = new FieldSecurityMetadata(
                    $lastNameField->getFieldName(),
                    $lastNameField->getLabel(),
                    $lastNameField->getPermissions(),
                    $lastNameField->getDescription(),
                    'lastNameAlias',
                    $lastNameField->isHidden()
                );
                $event->setFields($fields);

                return $event;
            });

        $this->cache->expects($this->exactly(2))
            ->method('save')
            ->with($cacheItem);

        return $entitySecurityMetadata;
    }

    public function testIsProtectedEntityWithoutCache(): void
    {
        $this->cache->expects($this->exactly(2))
            ->method('getItem')
            ->withConsecutive(
                ['short-' . EntitySecurityMetadataProvider::ACL_SECURITY_TYPE],
                ['full-' . EntitySecurityMetadataProvider::ACL_SECURITY_TYPE]
            )
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);
        $this->cacheItem->expects($this->exactly(2))
            ->method('set')
            ->willReturn($this->cacheItem);

        $entitySecurityMetadata = $this->expectLoadMetadata($this->cacheItem);

        $this->aclGroupProvider->expects($this->any())
            ->method('getGroup')
            ->willReturn($entitySecurityMetadata->getGroup());

        $this->assertTrue($this->provider->isProtectedEntity($entitySecurityMetadata->getClassName()));
        // test local cache
        $this->assertTrue($this->provider->isProtectedEntity($entitySecurityMetadata->getClassName()));
    }

    /**
     * @dataProvider isProtectedEntityDataProvider
     */
    public function testIsProtectedEntity(
        string $entityClass,
        string $entityGroup,
        string $group,
        bool $expected
    ): void {
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with('short-' . EntitySecurityMetadataProvider::ACL_SECURITY_TYPE)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects($this->once())
            ->method('get')
            ->willReturn([\stdClass::class => [$entityGroup, []]]);

        $this->aclGroupProvider->expects($this->any())
            ->method('getGroup')
            ->willReturn($group);

        $this->assertSame($expected, $this->provider->isProtectedEntity($entityClass));
        // test local cache
        $this->assertSame($expected, $this->provider->isProtectedEntity($entityClass));
    }

    public function isProtectedEntityDataProvider(): array
    {
        return [
            'no group, supported entity'          => [
                'entityClass' => \stdClass::class,
                'entityGroup' => '',
                'group'       => '',
                'expected'    => true
            ],
            'no group, unsupported entity'        => [
                'entityClass' => 'UnknownClass',
                'entityGroup' => '',
                'group'       => '',
                'expected'    => false
            ],
            'supported group, supported entity'   => [
                'entityClass' => \stdClass::class,
                'entityGroup' => 'SomeGroup',
                'group'       => 'SomeGroup',
                'expected'    => true
            ],
            'supported group, unsupported entity' => [
                'entityClass' => 'UnknownClass',
                'entityGroup' => 'SomeGroup',
                'group'       => 'SomeGroup',
                'expected'    => false
            ],
            'unsupported group, supported entity' => [
                'entityClass' => \stdClass::class,
                'entityGroup' => 'SomeGroup',
                'group'       => 'AnotherGroup',
                'expected'    => false
            ]
        ];
    }

    public function testGetProtectedFieldNameWithoutCache(): void
    {
        $this->cache->expects($this->exactly(2))
            ->method('getItem')
            ->withConsecutive(
                ['short-' . EntitySecurityMetadataProvider::ACL_SECURITY_TYPE],
                ['full-' . EntitySecurityMetadataProvider::ACL_SECURITY_TYPE]
            )
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);
        $this->cacheItem->expects($this->exactly(2))
            ->method('set')
            ->willReturn($this->cacheItem);

        $entitySecurityMetadata = $this->expectLoadMetadata($this->cacheItem);

        $this->assertEquals(
            'lastNameAlias',
            $this->provider->getProtectedFieldName($entitySecurityMetadata->getClassName(), 'lastName')
        );
        // test local cache
        $this->assertEquals(
            'lastNameAlias',
            $this->provider->getProtectedFieldName($entitySecurityMetadata->getClassName(), 'lastName')
        );
    }

    /**
     * @dataProvider getProtectedFieldNameDataProvider
     */
    public function testGetProtectedFieldName(
        string $entityClass,
        string $fieldName,
        array $fieldAliases,
        string $expected
    ): void {
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with('short-' . EntitySecurityMetadataProvider::ACL_SECURITY_TYPE)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects($this->once())
            ->method('get')
            ->willReturn([\stdClass::class => ['', $fieldAliases]]);

        $this->assertEquals($expected, $this->provider->getProtectedFieldName($entityClass, $fieldName));
        // test local cache
        $this->assertEquals($expected, $this->provider->getProtectedFieldName($entityClass, $fieldName));
    }

    public function getProtectedFieldNameDataProvider(): array
    {
        return [
            'no field alias, supported entity'    => [
                'entityClass'  => \stdClass::class,
                'fieldName'    => 'field',
                'fieldAliases' => [],
                'expected'     => 'field'
            ],
            'no field alias, unsupported entity'  => [
                'entityClass'  => 'UnknownClass',
                'fieldName'    => 'field',
                'fieldAliases' => [],
                'expected'     => 'field'
            ],
            'has field alias, supported entity'   => [
                'entityClass'  => \stdClass::class,
                'fieldName'    => 'field',
                'fieldAliases' => ['field' => 'alias'],
                'expected'     => 'alias'
            ],
            'has field alias, unsupported entity' => [
                'entityClass'  => 'UnknownClass',
                'fieldName'    => 'field',
                'fieldAliases' => ['field' => 'alias'],
                'expected'     => 'field'
            ]
        ];
    }

    public function testGetEntitiesWithoutCache(): void
    {
        $this->cache->expects($this->exactly(2))
            ->method('getItem')
            ->withConsecutive(
                ['full-' . EntitySecurityMetadataProvider::ACL_SECURITY_TYPE],
                ['short-' . EntitySecurityMetadataProvider::ACL_SECURITY_TYPE]
            )
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);
        $this->cacheItem->expects($this->exactly(2))
            ->method('set')
            ->willReturn($this->cacheItem);

        $entitySecurityMetadata = $this->expectLoadMetadata($this->cacheItem);

        $this->assertEquals([$entitySecurityMetadata], $this->provider->getEntities());
        // test local cache
        $this->assertEquals([$entitySecurityMetadata], $this->provider->getEntities());
    }

    public function testGetEntitiesWithCache(): void
    {
        $entitySecurityMetadata = $this->getEntitySecurityMetadata();

        $this->cache->expects($this->once())
            ->method('getItem')
            ->with('full-' . EntitySecurityMetadataProvider::ACL_SECURITY_TYPE)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects($this->once())
            ->method('get')
            ->willReturn([\stdClass::class => $entitySecurityMetadata]);


        $this->assertEquals([$entitySecurityMetadata], $this->provider->getEntities());
        // test local cache
        $this->assertEquals([$entitySecurityMetadata], $this->provider->getEntities());
    }
}
