<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestClass;

class AttachmentProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentAssociationHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentManager;

    /**
     * @var AttachmentProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attachmentProvider;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachmentAssociationHelper = $this
            ->getMockBuilder('Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachmentManager = $this
            ->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();


        $this->attachmentProvider =
            new AttachmentProvider($this->em, $this->attachmentAssociationHelper, $this->attachmentManager);
    }

    public function testGetEntityAttachments()
    {
        $entity = new TestClass();

        $this->attachmentAssociationHelper->expects($this->once())
            ->method('isAttachmentAssociationEnabled')
            ->with(get_class($entity))
            ->willReturn(true);

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroAttachmentBundle:Attachment')
            ->willReturn($repo);

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $qb->expects($this->once())
            ->method('leftJoin')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('where')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('setParameter')
            ->willReturn($qb);

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getSQL', 'getResult', '_doExecute'])
            ->getMock();

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn('result');

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($qb);

        $result = $this->attachmentProvider->getEntityAttachments($entity);
        $this->assertEquals('result', $result);
    }

    public function testUnsupportedAttachments()
    {
        $entity = new TestClass();

        $this->attachmentAssociationHelper->expects($this->once())
            ->method('isAttachmentAssociationEnabled')
            ->with(get_class($entity))
            ->willReturn(false);

        $result = $this->attachmentProvider->getEntityAttachments($entity);
        $this->assertEquals([], $result);
    }
}
