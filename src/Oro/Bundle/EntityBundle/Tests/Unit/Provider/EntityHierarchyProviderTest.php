<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;

class EntityHierarchyProviderTest extends OrmTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\EntityBundle\Tests\Unit\Provider\Fixtures\Hierarchy';

    /**
     * @var EntityHierarchyProvider
     */
    protected $provider;

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            self::ENTITY_NAMESPACE
        );

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($metadataDriver);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $config1 = new Config(new EntityConfigId('extend', self::ENTITY_NAMESPACE . '\TestEntity1'));
        $config2 = new Config(new EntityConfigId('extend', self::ENTITY_NAMESPACE . '\TestEntity2'));
        $config3 = new Config(new EntityConfigId('extend', self::ENTITY_NAMESPACE . '\TestEntity3'));

        $newCustomEntityConfig = new Config(
            new EntityConfigId('extend', ExtendConfigDumper::ENTITY . '\TestEntity4')
        );
        $newCustomEntityConfig->set('state', ExtendScope::STATE_NEW);

        $toBeDeletedCustomEntityConfig = new Config(
            new EntityConfigId('extend', ExtendConfigDumper::ENTITY . '\TestEntity5')
        );
        $toBeDeletedCustomEntityConfig->set('state', ExtendScope::STATE_DELETE);

        $deletedCustomEntityConfig = new Config(
            new EntityConfigId('extend', ExtendConfigDumper::ENTITY . '\TestEntity6')
        );
        $deletedCustomEntityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $deletedCustomEntityConfig->set('is_deleted', true);

        $entityConfigs = [
            $config1,
            $config2,
            $config3,
            $newCustomEntityConfig,
            $toBeDeletedCustomEntityConfig,
            $deletedCustomEntityConfig
        ];

        $extendConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue($entityConfigs));

        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($em));

        $this->provider = new EntityHierarchyProvider($extendConfigProvider, $doctrine);
    }

    public function testGetHierarchy()
    {
        $this->assertEquals(
            [
                self::ENTITY_NAMESPACE . '\TestEntity1' => [
                    self::ENTITY_NAMESPACE . '\BaseEntity'
                ],
                self::ENTITY_NAMESPACE . '\TestEntity2' => [
                    self::ENTITY_NAMESPACE . '\BaseEntity'
                ],
            ],
            $this->provider->getHierarchy()
        );
    }

    /**
     * @dataProvider getHierarchyForClassNameProvider
     */
    public function testGetHierarchyForClassName($className, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->provider->getHierarchyForClassName($className)
        );
    }

    public function getHierarchyForClassNameProvider()
    {
        return [
            [self::ENTITY_NAMESPACE . '\TestEntity1', [self::ENTITY_NAMESPACE . '\BaseEntity']],
            [self::ENTITY_NAMESPACE . '\TestEntity2', [self::ENTITY_NAMESPACE . '\BaseEntity']],
            [self::ENTITY_NAMESPACE . '\TestEntity3', []],
            [self::ENTITY_NAMESPACE . '\BaseEntity', []],
        ];
    }
}
