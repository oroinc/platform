<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider as Provider;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\TranslatorInterface;

class EntitySecurityMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $cache;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $securityConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $extendConfigProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var EntitySecurityMetadata */
    protected $entity;

    /**
     * @var Config
     */
    protected $extendConfig;

    /** @var AclGroupProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $aclGroupProvider;

    protected function setUp()
    {
        $this->securityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->extendConfig = new Config(new EntityConfigId('extend', \stdClass::class));
        $this->extendConfig->set('state', ExtendScope::STATE_ACTIVE);
        $this->cache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\CacheProvider',
            array(),
            '',
            false,
            true,
            true,
            array('fetch', 'save', 'delete', 'deleteAll')
        );

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->aclGroupProvider = $this->createMock(AclGroupProviderInterface::class);

        $this->entity = new EntitySecurityMetadata(
            Provider::ACL_SECURITY_TYPE,
            \stdClass::class,
            'SomeGroup',
            'SomeLabel',
            [],
            null,
            '',
            [
                'cityName' => new FieldSecurityMetadata(
                    'cityName',
                    'stdclass.city_name.label',
                    []
                ),
                'firstName' => new FieldSecurityMetadata(
                    'firstName',
                    'stdclass.first_name.label',
                    ['VIEW', 'CREATE']
                ),
                'lastName' => new FieldSecurityMetadata(
                    'lastName',
                    'stdclass.last_name.label',
                    []
                )
            ]
        );

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->extendConfig));
    }

    /**
     * @dataProvider groupDataProvider
     *
     * @param string $class
     * @param string $group
     * @param bool $expected
     */
    public function testIsProtectedEntity($class, $group, $expected)
    {
        $this->entity->setTranslated(true);

        $this->cache->expects($this->any())
            ->method('fetch')
            ->with(Provider::ACL_SECURITY_TYPE)
            ->will($this->returnValue(array(\stdClass::class => $this->entity)));

        $eventDispatcher = $this->getMockForAbstractClass(
            'Symfony\Component\EventDispatcher\EventDispatcherInterface'
        );

        $provider = new Provider(
            $this->securityConfigProvider,
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->doctrine,
            $this->createMock(TranslatorInterface::class),
            $this->cache,
            $eventDispatcher,
            $this->aclGroupProvider
        );

        $this->aclGroupProvider->expects($this->any())
            ->method('getGroup')
            ->willReturn($group);

        $this->assertEquals($expected, $provider->isProtectedEntity($class));
    }

    /**
     * @return array
     */
    public function groupDataProvider(): array
    {
        return [
            'no group supported' => [\stdClass::class, '', true],
            'no group unsupported' => ['UnknownClass', '', false],
            'supported group supported entity' => [\stdClass::class, 'SomeGroup', true],
            'supported group unsupported entity' => ['UnknownClass', 'SomeGroup', false],
            'unsupported group supported entity' => [\stdClass::class, 'UnsupportedGroup', false],
        ];
    }

    public function testGetEntities()
    {
        $entityConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $entityConfig->expects($this->at(0))
            ->method('get')
            ->with('label')
            ->will($this->returnValue('SomeLabel'));

        $this->entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(\stdClass::class)
            ->will($this->returnValue($entityConfig));

        $this->setTestConfig();

        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()->getMock();
        $metadataFactory = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()->getMock();
        $manager->expects($this->any())->method('getMetadataFactory')->willReturn($metadataFactory);
        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->identifier = ['id'];
        $metadataFactory->expects($this->any())->method('getMetadataFor')->willReturn($metadata);
        $this->doctrine->expects($this->any())->method('getManagerForClass')->willReturn($manager);

        $this->cache->expects($this->at(0))
            ->method('fetch')
            ->with(Provider::ACL_SECURITY_TYPE)
            ->will($this->returnValue(false));
        $this->cache->expects($this->at(2))
            ->method('fetch')
            ->with(Provider::ACL_SECURITY_TYPE)
            ->will($this->returnValue(array(\stdClass::class => $this->entity)));
        $this->cache->expects($this->once())
            ->method('save')
            ->with(Provider::ACL_SECURITY_TYPE, array(\stdClass::class => $this->entity));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($value) {
                return 'translated: ' . $value;
            });

        $eventDispatcher = $this->getMockForAbstractClass(
            'Symfony\Component\EventDispatcher\EventDispatcherInterface'
        );

        $provider = new Provider(
            $this->securityConfigProvider,
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->doctrine,
            $translator,
            $this->cache,
            $eventDispatcher,
            $this->aclGroupProvider
        );

        // call without cache
        $result = $provider->getEntities();
        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf('Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata', $result);

        $expectedEntity = $this->getExpectedEntity();
        $this->assertEquals([$expectedEntity], $result);

        // call with local cache
        $result = $provider->getEntities();
        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf('Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata', $result);
        $this->assertEquals([$expectedEntity], $result);

        // call with cache
        $provider = new Provider(
            $this->securityConfigProvider,
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->doctrine,
            $translator,
            $this->cache,
            $eventDispatcher,
            $this->aclGroupProvider
        );
        $result = $provider->getEntities();
        $this->assertCount(1, $result);
        $this->assertEquals([$expectedEntity], $result);
    }

    public function testClearCache()
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('SomeType');

        $this->cache->expects($this->once())
            ->method('deleteAll');

        $eventDispatcher = $this->getMockForAbstractClass(
            'Symfony\Component\EventDispatcher\EventDispatcherInterface'
        );

        $provider = new Provider(
            $this->securityConfigProvider,
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->doctrine,
            $this->createMock(TranslatorInterface::class),
            $this->cache,
            $eventDispatcher,
            $this->aclGroupProvider
        );

        $provider->clearCache('SomeType');
        $provider->clearCache();
    }

    protected function setTestConfig()
    {
        $securityConfigId = new EntityConfigId('security', \stdClass::class);
        $securityConfig = new Config($securityConfigId);
        $securityConfig->set('type', Provider::ACL_SECURITY_TYPE);
        $securityConfig->set('permissions', 'All');
        $securityConfig->set('group_name', 'SomeGroup');
        $securityConfig->set('category', '');
        $securityConfig->set('field_acl_supported', true);
        $securityConfig->set('field_acl_enabled', true);

        $securityConfigs = array($securityConfig);

        $idFieldConfigId = new FieldConfigId('security', \stdClass::class, 'id');
        $idFieldConfig = new Config($idFieldConfigId);

        $firstNameConfigId = new FieldConfigId('security', \stdClass::class, 'firstName');
        $firstNameFieldConfig = new Config($firstNameConfigId);
        $firstNameFieldConfig->set('permissions', 'VIEW;CREATE');

        $lastNameConfigId = new FieldConfigId('security', \stdClass::class, 'lastName');
        $lastNameFieldConfig = new Config($lastNameConfigId);
        $lastNameFieldConfig->set('permissions', 'All');

        $cityNameConfigId = new FieldConfigId('security', \stdClass::class, 'cityName');
        $cityNameFieldConfig = new Config($cityNameConfigId);

        $fieldsConfig = [$idFieldConfig, $firstNameFieldConfig, $lastNameFieldConfig, $cityNameFieldConfig];

        $this->securityConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->willReturnMap(
                [
                    [null, true, $securityConfigs],
                    [\stdClass::class, false, $fieldsConfig]
                ]
            );
    }

    /**
     * @return EntitySecurityMetadata
     */
    private function getExpectedEntity(): EntitySecurityMetadata
    {
        $expectedEntity = clone $this->entity;
        $expectedEntity->setLabel('translated: ' . $expectedEntity->getLabel());
        $expectedFields = [];
        foreach ($expectedEntity->getFields() as $key => $field) {
            $expectedField = clone $field;
            $expectedField->setLabel('translated: ' . $expectedField->getLabel());
            $expectedFields[$key] = $expectedField;
        }
        $expectedEntity->setFields($expectedFields);
        $expectedEntity->setTranslated(true);

        return $expectedEntity;
    }
}
