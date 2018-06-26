<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRegistry;

class TemplateEntityRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var TemplateEntityRegistry */
    protected $entityRegistry;

    protected function setUp()
    {
        $this->entityRegistry = new TemplateEntityRegistry();
    }

    public function testGettersAndSetters()
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $this->entityRegistry->addEntity('Test\Entity', 'test1', $entity1);
        $this->entityRegistry->addEntity('Test\Entity', 'test2', $entity2);

        // entity1
        $this->assertTrue(
            $this->entityRegistry->hasEntity('Test\Entity', 'test1')
        );
        $this->assertSame(
            $entity1,
            $this->entityRegistry->getEntity('Test\Entity', 'test1')
        );

        // entity2
        $this->assertTrue(
            $this->entityRegistry->hasEntity('Test\Entity', 'test2')
        );
        $this->assertSame(
            $entity2,
            $this->entityRegistry->getEntity('Test\Entity', 'test2')
        );

        // unknown entity
        $this->assertFalse(
            $this->entityRegistry->hasEntity('Test\Entity', 'test3')
        );
        $this->expectException('Oro\Bundle\ImportExportBundle\Exception\LogicException');
        $this->expectExceptionMessage('The entity "Test\Entity" with key "test3" does not exist.');
        $this->entityRegistry->getEntity('Test\Entity', 'test3');
    }

    public function testDuplicateAdd()
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $this->entityRegistry->addEntity('Test\Entity', 'test1', $entity1);

        $this->expectException('Oro\Bundle\ImportExportBundle\Exception\LogicException');
        $this->expectExceptionMessage('The entity "Test\Entity" with key "test1" is already exist.');
        $this->entityRegistry->addEntity('Test\Entity', 'test1', $entity2);
    }

    public function testGetDataForSingleEntity()
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $this->entityRegistry->addEntity('Test\Entity1', 'test1', $entity1);

        $repository1 = $this->createMock(
            'Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface'
        );
        $repository2 = $this->createMock(
            'Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface'
        );

        $templateManager = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager')
            ->disableOriginalConstructor()
            ->getMock();

        $templateManager->expects($this->at(0))
            ->method('getEntityRepository')
            ->with('Test\Entity1')
            ->will($this->returnValue($repository1));
        $repository1->expects($this->once())
            ->method('fillEntityData')
            ->with('test1', $this->identicalTo($entity1))
            ->will(
                $this->returnCallback(
                    function ($key, $entity) use ($entity2) {
                        $this->entityRegistry->addEntity('Test\Entity2', 'test2', $entity2);
                    }
                )
            );
        $templateManager->expects($this->at(1))
            ->method('getEntityRepository')
            ->with('Test\Entity2')
            ->will($this->returnValue($repository2));
        $repository2->expects($this->once())
            ->method('fillEntityData')
            ->with('test2', $this->identicalTo($entity2));

        $data = $this->entityRegistry->getData($templateManager, 'Test\Entity1', 'test1');
        $data = iterator_to_array($data);
        $this->assertCount(1, $data);

        $this->assertSame($entity1, current($data));
    }

    public function testGetDataForSeveralEntity()
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();
        $entity3 = new \stdClass();

        $this->entityRegistry->addEntity('Test\Entity1', 'test1', $entity1);

        $repository1 = $this->createMock(
            'Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface'
        );
        $repository2 = $this->createMock(
            'Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface'
        );

        $templateManager = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager')
            ->disableOriginalConstructor()
            ->getMock();

        $templateManager->expects($this->at(0))
            ->method('getEntityRepository')
            ->with('Test\Entity1')
            ->will($this->returnValue($repository1));
        $repository1->expects($this->at(0))
            ->method('fillEntityData')
            ->with('test1', $this->identicalTo($entity1))
            ->will(
                $this->returnCallback(
                    function ($key, $entity) use ($entity2) {
                        $this->entityRegistry->addEntity('Test\Entity2', 'test2', $entity2);
                    }
                )
            );
        $templateManager->expects($this->at(1))
            ->method('getEntityRepository')
            ->with('Test\Entity2')
            ->will($this->returnValue($repository2));
        $repository2->expects($this->once())
            ->method('fillEntityData')
            ->with('test2', $this->identicalTo($entity2))
            ->will(
                $this->returnCallback(
                    function ($key, $entity) use ($entity3) {
                        $this->entityRegistry->addEntity('Test\Entity1', 'test3', $entity3);
                    }
                )
            );
        $templateManager->expects($this->at(2))
            ->method('getEntityRepository')
            ->with('Test\Entity1')
            ->will($this->returnValue($repository1));
        $repository1->expects($this->at(1))
            ->method('fillEntityData')
            ->with('test3', $this->identicalTo($entity3));

        $data = $this->entityRegistry->getData($templateManager, 'Test\Entity1');
        $data = iterator_to_array($data);
        $this->assertCount(2, $data);

        $this->assertSame($entity1, $data[0]);
        $this->assertSame($entity3, $data[1]);
    }
}
