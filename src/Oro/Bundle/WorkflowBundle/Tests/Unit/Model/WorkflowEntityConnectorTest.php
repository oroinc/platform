<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowSystemConfigManager;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub;

class WorkflowEntityConnectorTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowEntityConnector */
    protected $entityConnector;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var WorkflowSystemConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowConfigManager;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->workflowConfigManager = $this->getMockBuilder(WorkflowSystemConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConnector = new WorkflowEntityConnector($this->registry, $this->workflowConfigManager);
    }

    public function testIsApplicableEntityConvertsObjectToClassName()
    {
        $this->workflowConfigManager->expects($this->once())->method('isConfigurable')->with(EntityStub::class)
            ->willReturn(false);

        $this->registry->expects($this->never())->method('getManagerForClass');

        $this->assertFalse($this->entityConnector->isApplicableEntity(new EntityStub(42)));
    }

    public function testIsApplicableEntityNotConfigurable()
    {
        $this->workflowConfigManager->expects($this->once())->method('isConfigurable')->with(EntityStub::class)
            ->willReturn(false);

        $this->registry->expects($this->never())->method('getManagerForClass');

        $this->assertFalse($this->entityConnector->isApplicableEntity(EntityStub::class));
    }

    public function testIsApplicableEntityNonManageable()
    {
        $this->workflowConfigManager->expects($this->once())->method('isConfigurable')->with(EntityStub::class)
            ->willReturn(true);

        $this->registry->expects($this->once())->method('getManagerForClass')
            ->with(EntityStub::class)
            ->willReturn(null);

        $this->setExpectedException(NotManageableEntityException::class);

        $this->assertFalse($this->entityConnector->isApplicableEntity(new EntityStub(42)));
    }

    public function testIsApplicableEntityNotSupportCompositePrimaryKeys()
    {
        $this->workflowConfigManager->expects($this->once())->method('isConfigurable')->with(EntityStub::class)
            ->willReturn(true);

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
        $this->workflowConfigManager->expects($this->once())->method('isConfigurable')->with(EntityStub::class)
            ->willReturn(true);

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
