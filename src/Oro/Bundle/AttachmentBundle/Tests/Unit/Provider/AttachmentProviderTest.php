<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentConfig;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestClass;

class AttachmentProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var AttachmentConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attachmentConfig;

    /**
     * @var AttachmentProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attachmentProvider;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachmentConfig = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachmentProvider = new AttachmentProvider($this->em, $this->attachmentConfig);
    }

    public function testGetEntityAttachments()
    {
        $entity = new TestClass();

        $this->attachmentConfig->expects($this->once())
            ->method('isAttachmentAssociationEnabled')
            ->with($entity)
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

        $this->attachmentConfig->expects($this->once())
            ->method('isAttachmentAssociationEnabled')
            ->with($entity)
            ->willReturn(false);

        $result = $this->attachmentProvider->getEntityAttachments($entity);
        $this->assertEquals([], $result);
    }
}
