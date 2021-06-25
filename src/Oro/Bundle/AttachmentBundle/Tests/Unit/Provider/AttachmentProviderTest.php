<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestClass;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;

class AttachmentProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $attachmentAssociationHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $attachmentManager;

    /** @var AttachmentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentProvider;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->attachmentAssociationHelper = $this->createMock(AttachmentAssociationHelper::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);

        $this->attachmentProvider = new AttachmentProvider(
            $this->em,
            $this->attachmentAssociationHelper,
            $this->attachmentManager
        );
    }

    public function testGetEntityAttachments()
    {
        $entity = new TestClass();

        $this->attachmentAssociationHelper->expects($this->once())
            ->method('isAttachmentAssociationEnabled')
            ->with(get_class($entity))
            ->willReturn(true);

        $repo = $this->createMock(EntityRepository::class);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroAttachmentBundle:Attachment')
            ->willReturn($repo);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('leftJoin')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('where')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('setParameter')
            ->willReturn($qb);

        $query = $this->createMock(AbstractQuery::class);

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
