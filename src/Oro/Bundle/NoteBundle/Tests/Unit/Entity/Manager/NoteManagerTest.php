<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
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
    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $em;

    private AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject $authorizationChecker;

    private AclHelper|\PHPUnit\Framework\MockObject\MockObject $aclHelper;

    private EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject $entityNameResolver;

    private PictureSourcesProviderInterface|\PHPUnit\Framework\MockObject\MockObject $pictureSourcesProvider;

    private NoteManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->pictureSourcesProvider = $this->createMock(PictureSourcesProviderInterface::class);
        $attachmentAssociationHelper = $this->createMock(AttachmentAssociationHelper::class);
        $attachmentManager = $this->createMock(AttachmentManager::class);

        $attachmentProvider = new AttachmentProviderStub(
            $this->em,
            $attachmentAssociationHelper,
            $attachmentManager,
            $this->pictureSourcesProvider
        );

        $this->manager = new NoteManager(
            $this->em,
            $this->authorizationChecker,
            $this->aclHelper,
            $this->entityNameResolver,
            $attachmentProvider,
            $this->pictureSourcesProvider
        );
    }

    public function testGetList(): void
    {
        $entityClass = 'Test\Entity';
        $entityId    = 123;
        $sorting     = 'DESC';
        $result      = ['result'];

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $repo = $this->createMock(NoteRepository::class);
        $this->em->expects(self::once())
            ->method('getRepository')
            ->with('OroNoteBundle:Note')
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getAssociatedNotesQueryBuilder')
            ->with($entityClass, $entityId)
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('orderBy')
            ->with('note.createdAt', $sorting)
            ->willReturnSelf();
        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($qb), 'VIEW', ['checkRelations' => false])
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);

        self::assertEquals(
            $result,
            $this->manager->getList($entityClass, $entityId, $sorting)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetEntityViewModels(): void
    {
        $createdByAvatar = new File();
        $createdBy = $this->createMock(TestUser::class);
        $createdBy->expects(self::once())
            ->method('getId')
            ->willReturn(100);
        $createdBy->expects(self::once())
            ->method('getAvatar')
            ->willReturn($createdByAvatar);
        $updatedBy = $this->createMock(TestUser::class);
        $updatedBy->expects(self::once())
            ->method('getId')
            ->willReturn(100);
        $updatedBy->expects(self::once())
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

        $this->authorizationChecker->expects(self::exactly(4))
            ->method('isGranted')
            ->willReturnMap([
                ['EDIT', $note, true],
                ['DELETE', $note, false],
                ['VIEW', $createdBy, true],
                ['VIEW', $updatedBy, false]
            ]);

        $this->entityNameResolver->expects(self::exactly(2))
            ->method('getName')
            ->willReturnMap([
                [$createdBy, null, null, 'User1'],
                [$updatedBy, null, null, 'User2']
            ]);

        $this->pictureSourcesProvider->expects(self::exactly(2))
            ->method('getFilteredPictureSources')
            ->willReturnMap([
                [
                    $createdByAvatar,
                    'avatar_xsmall',
                    [
                        'src' => 'image1_xsmall.jpg',
                        'sources' => [
                            [
                                'srcset' => 'image1_xsmall.jpg.webp',
                                'type' => 'image/webp',
                            ],
                        ],
                    ]
                ],
                [
                    null,
                    'avatar_xsmall',
                    [
                        'src' => null,
                        'sources' => [],
                    ]
                ],
            ]);

        self::assertEquals(
            [
                [
                    'id' => 123,
                    'message' => 'test message',
                    'createdAt' => '2014-01-20T10:30:40+00:00',
                    'updatedAt' => '2014-01-21T10:30:40+00:00',
                    'hasUpdate' => true,
                    'editable' => true,
                    'removable' => false,
                    'createdBy' => 'User1',
                    'createdBy_id' => 100,
                    'createdBy_viewable' => true,
                    'createdBy_avatarPicture' => [
                        'src' => 'image1_xsmall.jpg',
                        'sources'  => [
                            [
                                'srcset' => 'image1_xsmall.jpg.webp',
                                'type' => 'image/webp',
                            ]
                        ],
                    ],
                    'updatedBy' => 'User2',
                    'updatedBy_id' => 100,
                    'updatedBy_viewable' => false,
                    'updatedBy_avatarPicture' => [
                        'src' => null,
                        'sources' => [],
                    ],
                ]
            ],
            $this->manager->getEntityViewModels([$note])
        );
    }
}
