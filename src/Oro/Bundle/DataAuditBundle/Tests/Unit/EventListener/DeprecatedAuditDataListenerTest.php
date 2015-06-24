<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\EventListener\DeprecatedAuditDataListener;

class DeprecatedAuditDataListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $em;
    protected $uow;

    protected $deprecatedAuditDataListener;

    public function setUp()
    {
        $this->uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->deprecatedAuditDataListener = new DeprecatedAuditDataListener();
    }

    public function testListenerCanBeCreated()
    {
        return new DeprecatedAuditDataListener();
    }

    /**
     * @depends testListenerCanBeCreated
     */
    public function testOnFlush(DeprecatedAuditDataListener $deprecatedAuditDataListener)
    {
        $audit = new Audit();
        $audit->setObjectClass('class');
        $audit->setData([
            'stringField' => [
                'old' => 'oldValue',
                'new' => 'newValue',
            ],
            'intField' => [
                'old' => ['value' => 3],
                'new' => ['value' => 5],
            ]
        ]);

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$audit]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));

        $onFlushEventArgs = new OnFlushEventArgs($this->em);
        $deprecatedAuditDataListener->onFlush($onFlushEventArgs);

        return $audit;
    }

    /**
     * @depends testOnFlush
     * @depends testListenerCanBeCreated
     */
    public function testPostFlush(Audit $audit, DeprecatedAuditDataListener $deprecatedAuditDataListener)
    {
        $classMetadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $classMetadata->expects($this->exactly(2))
            ->method('getTypeOfField')
            ->withConsecutive(
                ['stringField'],
                ['intField']
            )
            ->willReturnOnConsecutiveCalls('string', 'integer');

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with('class')
            ->will($this->returnValue($classMetadata));

        $this->em->expects($this->once())
            ->method('flush');

        $this->assertEmpty($audit->getFields());
        $postFlushEventArgs = new PostFlushEventArgs($this->em);
        $deprecatedAuditDataListener->postFlush($postFlushEventArgs);

        $this->assertCount(2, $audit->getFields());
        $stringField = $audit->getField('stringField');
        $this->assertEquals('oldValue', $stringField->getOldValue());
        $this->assertEquals('newValue', $stringField->getNewValue());
        $this->assertEquals('text', $stringField->getDataType());

        $intField = $audit->getField('intField');
        $this->assertEquals(3, $intField->getOldValue());
        $this->assertEquals(5, $intField->getNewValue());
        $this->assertEquals('integer', $intField->getDataType());

        $this->assertNull($audit->getDeprecatedData());
    }
}
