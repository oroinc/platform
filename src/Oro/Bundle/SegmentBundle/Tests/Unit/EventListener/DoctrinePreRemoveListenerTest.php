<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SegmentBundle\EventListener\DoctrinePreRemoveListener;
use Oro\Bundle\SegmentBundle\Tests\Unit\Fixtures\StubEntity;

class DoctrinePreRemoveListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $cm;

    /** @var DoctrinePreRemoveListener */
    protected $listener;

    public function setUp()
    {
        $this->cm = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new DoctrinePreRemoveListener($this->cm);
    }

    public function tearDown()
    {
        unset($this->cm, $this->listener);
    }

    /**
     * @dataProvider preRemoveProvider
     *
     * @param bool $entityIsConfigurable
     */
    public function testPreRemove($entityIsConfigurable = false)
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $entity = new StubEntity();
        $args   = new LifecycleEventArgs($entity, $em);

        $this->cm->expects($this->any())->method('hasConfig')->will($this->returnValue($entityIsConfigurable));

        $repo = $this->getMockBuilder('Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository')
            ->disableOriginalConstructor()->getMock();
        $repo->expects($this->exactly((int)$entityIsConfigurable))->method('removeByEntity');
        $em->expects($this->exactly((int)$entityIsConfigurable))->method('getRepository')
            ->will($this->returnValue($repo));

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
}
