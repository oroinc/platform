<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\NoteBundle\Entity\Manager\NoteManager;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;
use Oro\Bundle\NoteBundle\Tests\Unit\Fixtures\TestUser;
use Oro\Bundle\NoteBundle\Tests\Unit\Stub\AttachmentProviderStub;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class NoteManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentManager;

    /** @var AttachmentAssociationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentAssociationHelper;

    /** @var NoteManager */
    private $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->attachmentAssociationHelper = $this->createMock(AttachmentAssociationHelper::class);

        $attachmentProvider = new AttachmentProviderStub(
            $this->em,
            $this->attachmentAssociationHelper,
            $this->attachmentManager
        );

        $this->manager = new NoteManager(
            $this->em,
            $this->authorizationChecker,
            $this->aclHelper,
            $this->entityNameResolver,
            $attachmentProvider,
            $this->attachmentManager
        );
    }

    public function testGetList()
    {
        $entityClass = 'Test\Entity';
        $entityId    = 123;
        $sorting     = 'DESC';
        $result      = ['result'];

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $repo = $this->createMock(NoteRepository::class);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroNoteBundle:Note')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('getAssociatedNotesQueryBuilder')
            ->with($entityClass, $entityId)
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('note.createdAt', $sorting)
            ->willReturnSelf();
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($this->identicalTo($qb), 'VIEW', ['checkRelations' => false])
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        $this->assertEquals(
            $result,
            $this->manager->getList($entityClass, $entityId, $sorting)
        );
    }

    public function testGetEntityViewModels()
    {
        $createdByAvatar = new File();
        $createdBy = $this->createMock(TestUser::class);
        $createdBy->expects($this->once())
            ->method('getId')
            ->willReturn(100);
        $createdBy->expects($this->once())
            ->method('getAvatar')
            ->willReturn($createdByAvatar);
        $updatedBy = $this->createMock(TestUser::class);
        $updatedBy->expects($this->once())
            ->method('getId')
            ->willReturn(100);
        $updatedBy->expects($this->once())
            ->method('getAvatar')
            ->willReturn(null);

        $note = new Note();
        ReflectionUtil::setId($note, 123);
        $note
            ->setMessage('test message')
            ->setCreatedAt(new \DateTime('2014-01-20 10:30:40', new \DateTimeZone('UTC')))
            ->setUpdatedAt(new \DateTime('2014-01-21 10:30:40', new \DateTimeZone('UTC')))
            ->setOwner($createdBy)
            ->setUpdatedBy($updatedBy);

        $this->authorizationChecker->expects($this->exactly(4))
            ->method('isGranted')
            ->willReturnMap([
                ['EDIT', $note, true],
                ['DELETE', $note, false],
                ['VIEW', $createdBy, true],
                ['VIEW', $updatedBy, false]
            ]);

        $this->entityNameResolver->expects($this->exactly(2))
            ->method('getName')
            ->willReturnMap([
                [$createdBy, null, null, 'User1'],
                [$updatedBy, null, null, 'User2']
            ]);

        $this->attachmentManager->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($this->identicalTo($createdByAvatar), 'avatar_xsmall')
            ->willReturn('image1_xsmall');

        $this->assertEquals(
            [
                [
                    'id'                 => 123,
                    'message'            => 'test message',
                    'createdAt'          => '2014-01-20T10:30:40+00:00',
                    'updatedAt'          => '2014-01-21T10:30:40+00:00',
                    'hasUpdate'          => true,
                    'editable'           => true,
                    'removable'          => false,
                    'createdBy'          => 'User1',
                    'createdBy_id'       => 100,
                    'createdBy_viewable' => true,
                    'createdBy_avatar'   => 'image1_xsmall',
                    'updatedBy'          => 'User2',
                    'updatedBy_id'       => 100,
                    'updatedBy_viewable' => false,
                    'updatedBy_avatar'   => null,
                ]
            ],
            $this->manager->getEntityViewModels([$note])
        );
    }
}
