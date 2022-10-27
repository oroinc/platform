<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class WorkflowEntityConnectorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const WORKFLOW_APPLICABLE_ENTITIES_CACHE_KEY_PREFIX = 'workflow_applicable_entity:';

    /** @var WorkflowEntityConnector */
    private $entityConnector;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->entityConnector = new WorkflowEntityConnector($this->registry);
    }

    /**
     * @param $class
     * @return string
     */
    private function cacheKey($class)
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey(
            self::WORKFLOW_APPLICABLE_ENTITIES_CACHE_KEY_PREFIX . $class
        );
    }

    public function testIsApplicableEntityConvertsObjectToClassName()
    {
        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        $cache = $this->createMock(CacheInterface::class);

        $this->setValue($this->entityConnector, 'cache', $cache);

        $cacheKey = $this->cacheKey(EntityStub::class);

        $cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(true);

        $this->assertTrue($this->entityConnector->isApplicableEntity(new EntityStub(42)));
    }

    public function testCacheStores()
    {
        $cache = $this->createMock(CacheInterface::class);

        $this->setValue($this->entityConnector, 'cache', $cache);

        $key = $this->cacheKey(EntityStub::class);

        $cache->expects(self::once())
            ->method('get')
            ->with($key)
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        //routine
        $om = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(EntityStub::class)
            ->willReturn($om);

        $metadata = new ClassMetadataInfo(EntityStub::class);
        $om->expects($this->once())
            ->method('getClassMetadata')
            ->with(EntityStub::class)
            ->willReturn($metadata);
        $metadata->setIdentifier(['a', 'b']);
        //metadata setup is for composite keys so internally isSupportedIdentifierType will return false

        $this->assertFalse($this->entityConnector->isApplicableEntity(new EntityStub(42)));
    }

    public function testIsApplicableEntityNonManageable()
    {
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(EntityStub::class)
            ->willReturn(null);

        $this->expectException(NotManageableEntityException::class);

        $this->assertFalse($this->entityConnector->isApplicableEntity(new EntityStub(42)));
    }

    public function testIsApplicableEntityNotSupportCompositePrimaryKeys()
    {
        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(EntityStub::class)
            ->willReturn($manager);
        $metadata = new ClassMetadataInfo(EntityStub::class);
        $metadata->setIdentifier(['id', 'other_field']);

        $manager->expects($this->once())
            ->method('getClassMetadata')
            ->with(EntityStub::class)
            ->willReturn($metadata);

        $this->assertFalse($this->entityConnector->isApplicableEntity(new EntityStub([42, 42])));
    }

    /**
     * @param string|object $type
     * @param bool $expected
     * @dataProvider typeSupportingProvider
     */
    public function testIsApplicableEntitySupportedTypes($type, $expected)
    {
        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(EntityStub::class)
            ->willReturn($manager);
        $metadata = new ClassMetadataInfo(EntityStub::class);
        $metadata->setIdentifier(['id']);
        $metadata->fieldMappings['id'] = ['type' => $type];

        $manager->expects($this->once())
            ->method('getClassMetadata')
            ->with(EntityStub::class)
            ->willReturn($metadata);

        $this->assertEquals($expected, $this->entityConnector->isApplicableEntity(new EntityStub([42, 42])));
    }

    public function typeSupportingProvider(): array
    {
        return [
            [Types::BIGINT, true],
            [Types::DECIMAL, true],
            [Types::INTEGER, true],
            [Types::SMALLINT, true],
            [Types::STRING, true],
            [Types::TEXT, false],
            [Types::BINARY, false],
            ['other', false],
            'type object to string conversion' => [Type::getType(Types::SMALLINT), true]
        ];
    }
}
