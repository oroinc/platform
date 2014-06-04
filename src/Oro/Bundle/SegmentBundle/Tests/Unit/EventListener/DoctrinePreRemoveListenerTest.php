<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SegmentBundle\EventListener\DoctrinePreRemoveListener;
use Oro\Bundle\SegmentBundle\Tests\Unit\Fixtures\StubEntity;

class DoctrinePreRemoveListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var DoctrinePreRemoveListener */
    protected $listener;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new DoctrinePreRemoveListener($this->configManager);
    }

    protected function tearDown()
    {
        unset($this->configManager, $this->listener, $this->entityManager);
    }

    /**
     * @dataProvider preRemoveProvider
     *
     * @param bool $entityIsConfigurable
     */
    public function testPreRemove($entityIsConfigurable = false)
    {
        $entity = new StubEntity();
        $args   = new LifecycleEventArgs($entity, $this->entityManager);

        $this->mockMetadata($entityIsConfigurable ? 1 : 0);
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->will($this->returnValue($entityIsConfigurable));

        $this->listener->preRemove($args);
    }

    /**
     * @return array
     */
    public function preRemoveProvider()
    {
        return [
            'should process all configurable entities' => [true],
            'should not process all entities'          => [false]
        ];
    }

    /**
     * @dataProvider postFlushProvider
     *
     * @param array $entities
     */
    public function testPostFlush($entities)
    {
        $this->mockMetadata(count($entities));
        $this->configManager->expects($this->exactly(count($entities)))
            ->method('hasConfig')
            ->will($this->returnValue(true));

        foreach ($entities as $entity) {
            $args = new LifecycleEventArgs($entity['entity'], $this->entityManager);
            $this->listener->preRemove($args);

        }

        $repository = $this->getMockBuilder('Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository')
            ->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())
            ->method('massRemoveByEntities')
            ->with($entities);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $args = new PostFlushEventArgs($this->entityManager);
        $this->listener->postFlush($args);
    }

    /**
     * @return array
     */
    public function postFlushProvider()
    {
        return [
            'one entity' => array(
                'entities' => $this->createEntities()
            ),
            'five entities' => array(
                'entities' => $this->createEntities(5)
            ),
        ];
    }

    protected function createEntities($count = 1)
    {
        $entities = array();
        for ($i = 0; $i < $count; $i++) {
            $entity = new StubEntity();
            $entity->setId($i);
            $entity->setName('name-' . $i);
            $entities[] = array(
                'id'     => $i,
                'entity' => $entity
            );
        }
        return $entities;
    }

    protected function mockMetadata($callCount)
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->setMethods(array('getIdentifierValues'))
            ->getMock();
        $metadata->expects($this->exactly($callCount))
            ->method('getIdentifierValues')
            ->will($this->returnCallback(
                function (StubEntity $currentEntity) {
                    return array($currentEntity->getId());
                }
            ));
        $this->entityManager->expects($this->exactly($callCount))
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));
    }
}
