<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub;
use Oro\Component\Testing\Unit\EntityTrait;

class WorkflowEntityConnectorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var WorkflowEntityConnector */
    protected $entityConnector;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->entityConnector = new WorkflowEntityConnector($this->registry);
    }

    protected function cacheKey($class)
    {
        return WorkflowEntityConnector::WORKFLOW_APPLICABLE_ENTITIES_CACHE_KEY_PREFIX . $class;
    }

    public function testIsApplicableEntityConvertsObjectToClassName()
    {
        $this->registry->expects($this->never())->method('getManagerForClass');

        $cache = $this->getMock(Cache::class);

        $this->setValue($this->entityConnector, 'cache', $cache);

        $cacheKey = $this->cacheKey(EntityStub::class);
        $cache->expects($this->once())
            ->method('contains')
            ->with($cacheKey)
            ->willReturn(true);

        $cache->expects($this->once())->method('fetch')->with($cacheKey)->willReturn(true);

        $this->assertTrue($this->entityConnector->isApplicableEntity(new EntityStub(42)));
    }

    public function testCacheStores()
    {
        $cache = $this->getMock(Cache::class);

        $this->setValue($this->entityConnector, 'cache', $cache);

        $key = $this->cacheKey(EntityStub::class);

        $cache->expects($this->once())->method('contains')->with($key)->willReturn(false);

        //routine
        $om = $this->getMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(EntityStub::class)
            ->willReturn($om);

        $metadata = new ClassMetadataInfo(EntityStub::class);
        $om->expects($this->once())->method('getClassMetadata')->with(EntityStub::class)->willReturn($metadata);
        $metadata->setIdentifier(['a', 'b']);
        //metadata setup is for composite keys so internally isSupportedIdentifierType will return false

        $cache->expects($this->once())->method('save')->with($key, false);

        $cache->expects($this->never())->method('fetch');

        $this->assertFalse($this->entityConnector->isApplicableEntity(new EntityStub(42)));
    }

    public function testIsApplicableEntityNonManageable()
    {
        $this->registry->expects($this->once())->method('getManagerForClass')
            ->with(EntityStub::class)
            ->willReturn(null);

        $this->setExpectedException(NotManageableEntityException::class);

        $this->assertFalse($this->entityConnector->isApplicableEntity(new EntityStub(42)));
    }

    public function testIsApplicableEntityNotSupportCompositePrimaryKeys()
    {
        $manager = $this->getMock(ObjectManager::class);
        $this->registry->expects($this->once())->method('getManagerForClass')
            ->with(EntityStub::class)
            ->willReturn($manager);
        $metadata = new ClassMetadataInfo(EntityStub::class);
        $metadata->setIdentifier(['id', 'other_field']);

        $manager->expects($this->once())->method('getClassMetadata')->with(EntityStub::class)->willReturn($metadata);

        $this->assertFalse($this->entityConnector->isApplicableEntity(new EntityStub([42, 42])));
    }

    /**
     * @param string|object $type
     * @param bool $expected
     * @dataProvider typeSupportingProvider
     */
    public function testIsApplicableEntitySupportedTypes($type, $expected)
    {
        $manager = $this->getMock(ObjectManager::class);
        $this->registry->expects($this->once())->method('getManagerForClass')
            ->with(EntityStub::class)
            ->willReturn($manager);
        $metadata = new ClassMetadataInfo(EntityStub::class);
        $metadata->setIdentifier(['id']);
        $metadata->fieldMappings['id'] = ['type' => $type];

        $manager->expects($this->once())->method('getClassMetadata')->with(EntityStub::class)->willReturn($metadata);

        $this->assertEquals($expected, $this->entityConnector->isApplicableEntity(new EntityStub([42, 42])));
    }

    /**
     * @return array[]
     */
    public function typeSupportingProvider()
    {
        return [
            [Type::BIGINT, true],
            [Type::DECIMAL, true],
            [Type::INTEGER, true],
            [Type::SMALLINT, true],
            [Type::STRING, true],
            [Type::TEXT, false],
            [Type::BINARY, false],
            ['other', false],
            'type object to string conversion' => [Type::getType(Type::SMALLINT), true]
        ];
    }
}
