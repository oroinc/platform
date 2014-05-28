<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
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

        $entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $entityIds = [
            new EntityConfigId('entity', self::ENTITY_NAMESPACE . '\TestEntity1'),
            new EntityConfigId('entity', self::ENTITY_NAMESPACE . '\TestEntity2'),
            new EntityConfigId('entity', self::ENTITY_NAMESPACE . '\TestEntity3'),
        ];

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));
        $entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->will($this->returnValue($entityIds));
        $entityConfigProvider->expects($this->once())
            ->method('getConfigManager')
            ->will($this->returnValue($configManager));

        $this->provider = new EntityHierarchyProvider($entityConfigProvider);
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
