<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class EntityHierarchyProviderTest extends OrmTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\EntityBundle\Tests\Unit\Provider\Fixtures\Hierarchy';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $extendConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $emMock;

    protected function setUp()
    {
        $metadataDriver = new AnnotationDriver(
            new AnnotationReader(),
            self::ENTITY_NAMESPACE
        );

        $this->emMock = $this->getTestEntityManager();
        $this->emMock->getConfiguration()->setMetadataDriverImpl($metadataDriver);

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
    public function testGetHierarchyForClassName($className, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->getProvider()->getHierarchyForClassName($className)
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

    /**
     * @dataProvider getNoManagerForClassNameProvider
     */
    public function testGetNoManagerForClassName($className, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->getProvider(false)->getHierarchyForClassName($className)
        );
    }

    public function getNoManagerForClassNameProvider()
    {
        return [
            [self::ENTITY_NAMESPACE . '\TestEntity1', []],
            [self::ENTITY_NAMESPACE . '\TestEntity2', []],
            [self::ENTITY_NAMESPACE . '\TestEntity3', []],
            [self::ENTITY_NAMESPACE . '\BaseEntity', []],
        ];
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject|null $emMock
     * @param boolean                                       $isReturnManager
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getDoctrineMock($emMock = null, $isReturnManager = true)
    {
        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($isReturnManager ? $emMock : null));

        return $doctrine;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getExtendConfigMock()
    {
        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

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
            ->will($this->returnValue($entityConfigs));

        return $extendConfigProvider;
    }

    /**
     * @param bool $isReturnManager return doctrine manager or not (null)
     *
     * @return EntityHierarchyProvider
     */
    protected function getProvider($isReturnManager = true)
    {
        return new EntityHierarchyProvider(
            new DoctrineHelper($this->getDoctrineMock($this->emMock, $isReturnManager)),
            $this->extendConfigProvider
        );
    }
}
