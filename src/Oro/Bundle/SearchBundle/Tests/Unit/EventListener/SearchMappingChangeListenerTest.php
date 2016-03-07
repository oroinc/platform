<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\SearchBundle\EventListener\SearchMappingChangeListener;

class SearchMappingChangeListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $em;
    protected $uow;
    protected $searchMappingProvider;

    protected $searchMappingChangeListener;

    public function setUp()
    {
        $this->searchMappingProvider = $this->getMockBuilder('Oro\Bundle\SearchBundle\Provider\SearchMappingProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->searchMappingChangeListener = new SearchMappingChangeListener($this->searchMappingProvider);
    }

    /**
     * @dataProvider configurationObjectsProvider
     */
    public function testCacheShouldBeClearedIfItIsPossibleThatMappingHasChangedDuringInsert($object)
    {
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$object]));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->will($this->returnValue([]));

        $this->searchMappingProvider->expects($this->once())
            ->method('clearMappingCache');

        $args = new OnFlushEventArgs($this->em);
        $this->searchMappingChangeListener->onFlush($args);
    }

    /**
     * @dataProvider configurationObjectsProvider
     */
    public function testCacheShouldBeClearedIfItIsPossibleThatMappingHasChangedDuringUpdate($object)
    {
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([$object]));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->will($this->returnValue([]));

        $this->searchMappingProvider->expects($this->once())
            ->method('clearMappingCache');

        $args = new OnFlushEventArgs($this->em);
        $this->searchMappingChangeListener->onFlush($args);
    }

    /**
     * @dataProvider configurationObjectsProvider
     */
    public function testCacheShouldBeClearedIfItIsPossibleThatMappingHasChangedDuringDelete($object)
    {
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->will($this->returnValue([$object]));

        $this->searchMappingProvider->expects($this->once())
            ->method('clearMappingCache');

        $args = new OnFlushEventArgs($this->em);
        $this->searchMappingChangeListener->onFlush($args);
    }

    public function configurationObjectsProvider()
    {
        $classNames = [
            'Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel',
            'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel',
        ];

        return array_map(function ($className) {
            return [
                $this->getMockBuilder($className)
                    ->disableOriginalConstructor()
                    ->getMock()
            ];
        }, $classNames);
    }

    public function testCacheShouldNotBeClearedIfNothingWasChanged()
    {
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->will($this->returnValue([]));

        $this->searchMappingProvider->expects($this->exactly(0))
            ->method('clearMappingCache');

        $args = new OnFlushEventArgs($this->em);
        $this->searchMappingChangeListener->onFlush($args);
    }

    /**
     * @dataProvider unimportantEntitiesProvider
     */
    public function testCacheShouldNotBeClearedIfUnimportatEntitiesChanged($insertions, $updates, $deletions)
    {
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue($insertions));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue($updates));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->will($this->returnValue($deletions));

        $this->searchMappingProvider->expects($this->exactly(0))
            ->method('clearMappingCache');

        $args = new OnFlushEventArgs($this->em);
        $this->searchMappingChangeListener->onFlush($args);
    }

    public function unimportantEntitiesProvider()
    {
        $object = $this->getMockBuilder('Oro\Bundle\SearchBundle\Entity\Item')
            ->disableOriginalConstructor()
            ->getMock();

        return [
            [[$object], [], []],
            [[], [$object], []],
            [[], [], [$object]],
        ];
    }
}
