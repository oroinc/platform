<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class EntityHierarchyProviderTest extends OrmTestCase
{
    private const ENTITY_NAMESPACE = 'Oro\Bundle\EntityBundle\Tests\Unit\Provider\Fixtures\Hierarchy';

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var EntityManagerInterface */
    private $emMock;

    protected function setUp(): void
    {
        $this->emMock = $this->getTestEntityManager();
        $this->emMock->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $this->extendConfigProvider = $this->getExtendConfigMock();
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
            $this->getProvider()->getHierarchy()
        );
    }

    /**
     * @dataProvider getHierarchyForClassNameProvider
     */
    public function testGetHierarchyForClassName(string $className, array $expected)
    {
        $this->assertEquals(
            $expected,
            $this->getProvider()->getHierarchyForClassName($className)
        );
    }

    public function getHierarchyForClassNameProvider(): array
    {
        return [
            [self::ENTITY_NAMESPACE . '\TestEntity1', [self::ENTITY_NAMESPACE . '\BaseEntity']],
            [self::ENTITY_NAMESPACE . '\TestEntity2', [self::ENTITY_NAMESPACE . '\BaseEntity']],
            [self::ENTITY_NAMESPACE . '\TestEntity3', []],
            [self::ENTITY_NAMESPACE . '\BaseEntity', []],
        ];
    }

    /**
     * @dataProvider getNoManagerForClassNameProvider
     */
    public function testGetNoManagerForClassName(string $className, array $expected)
    {
        $this->assertEquals(
            $expected,
            $this->getProvider(false)->getHierarchyForClassName($className)
        );
    }

    public function getNoManagerForClassNameProvider(): array
    {
        return [
            [self::ENTITY_NAMESPACE . '\TestEntity1', []],
            [self::ENTITY_NAMESPACE . '\TestEntity2', []],
            [self::ENTITY_NAMESPACE . '\TestEntity3', []],
            [self::ENTITY_NAMESPACE . '\BaseEntity', []],
        ];
    }

    /**
     * @return ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getExtendConfigMock()
    {
        $extendConfigProvider = $this->createMock(ConfigProvider::class);

        $config1 = new Config(new EntityConfigId('extend', self::ENTITY_NAMESPACE . '\TestEntity1'));
        $config1->set('is_extend', true);
        $config2 = new Config(new EntityConfigId('extend', self::ENTITY_NAMESPACE . '\TestEntity2'));
        $config2->set('is_extend', true);
        $config3 = new Config(new EntityConfigId('extend', self::ENTITY_NAMESPACE . '\TestEntity3'));
        $config3->set('is_extend', true);

        $newCustomEntityConfig = new Config(
            new EntityConfigId('extend', ExtendHelper::ENTITY_NAMESPACE . '\TestEntity4')
        );
        $newCustomEntityConfig->set('is_extend', true);
        $newCustomEntityConfig->set('state', ExtendScope::STATE_NEW);

        $toBeDeletedCustomEntityConfig = new Config(
            new EntityConfigId('extend', ExtendHelper::ENTITY_NAMESPACE . '\TestEntity5')
        );
        $toBeDeletedCustomEntityConfig->set('is_extend', true);
        $toBeDeletedCustomEntityConfig->set('state', ExtendScope::STATE_DELETE);

        $deletedCustomEntityConfig = new Config(
            new EntityConfigId('extend', ExtendHelper::ENTITY_NAMESPACE . '\TestEntity6')
        );
        $deletedCustomEntityConfig->set('is_extend', true);
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
            ->willReturn($entityConfigs);

        return $extendConfigProvider;
    }

    private function getProvider(bool $returnEntityManager = true): EntityHierarchyProvider
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($returnEntityManager ? $this->emMock : null);

        return new EntityHierarchyProvider(
            new DoctrineHelper($doctrine),
            $this->extendConfigProvider
        );
    }
}
