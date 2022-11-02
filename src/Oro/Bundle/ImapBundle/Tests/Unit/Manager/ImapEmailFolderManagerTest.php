<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ImapEmailFolderManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ImapConnector|\PHPUnit\Framework\MockObject\MockObject */
    private $connector;

    /** @var EntityManagerInterface|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    protected function setUp(): void
    {
        $this->connector = $this->createMock(ImapConnector::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    private function mockGetExistingFolders(array $result): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())->method('getResult')->willReturn($result);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())->method('select')->willReturn($qb);
        $qb->expects(self::once())->method('from')->willReturn($qb);
        $qb->expects(self::once())->method('leftJoin')->willReturn($qb);
        $qb->expects(self::once())->method('where')->willReturn($qb);
        $qb->expects(self::once())->method('setParameter')->willReturn($qb);
        $qb->expects(self::once())->method('getQuery')->willReturn($query);

        $this->em->expects(self::once())->method('createQueryBuilder')->willReturn($qb);
    }

    private function mockEmRequestsForRefreshFolders($getFoldersWithoutUidValidity, $getExistingFolders): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::any())
            ->method('getResult')
            ->willReturnOnConsecutiveCalls($getFoldersWithoutUidValidity, $getExistingFolders);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::any())->method('select')->willReturn($qb);
        $qb->expects(self::any())->method('from')->willReturn($qb);
        $qb->expects(self::any())->method('leftJoin')->willReturn($qb);
        $qb->expects(self::any())->method('where')->willReturn($qb);
        $qb->expects(self::any())->method('andWhere')->willReturn($qb);
        $qb->expects(self::any())->method('setParameter')->willReturn($qb);
        $qb->expects(self::any())->method('setMaxResults')->willReturn($qb);
        $qb->expects(self::any())->method('getQuery')->willReturn($query);

        $this->em->expects(self::any())->method('createQueryBuilder')->willReturn($qb);
    }

    private function createRemoteFolder(
        string $localName,
        string $globalName,
        array $flags = [],
        bool $selectable = true,
        array $subFolders = []
    ): Folder {
        $folder = new Folder($localName, $globalName, $selectable, $subFolders);
        $folder->setFlags($flags);

        return $folder;
    }

    public function testGetUidValidityOnFolder(): void
    {
        $uIDValidity = 567;
        $folder = new Folder('localName', 'globalName');

        $this->connector->expects(self::once())
            ->method('selectFolder')
            ->with('globalName');

        $this->connector->expects(self::once())
            ->method('getUidValidity')
            ->willReturn($uIDValidity);

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $this->createMock(EmailOrigin::class));

        self::assertEquals($uIDValidity, $manager->getUidValidity($folder));
    }

    public function testGetUidValidityOnEmailFolder(): void
    {
        $uIDValidity = 568;
        $folder = new EmailFolder();
        $folder->setFullName('fullName');

        $this->connector->expects(self::once())
            ->method('selectFolder')
            ->with('fullName');

        $this->connector->expects(self::once())
            ->method('getUidValidity')
            ->willReturn($uIDValidity);

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $this->createMock(EmailOrigin::class));

        self::assertEquals($uIDValidity, $manager->getUidValidity($folder));
    }

    public function testGetUidValidityOnStringFolderName(): void
    {
        $uIDValidity = 569;
        $folderName = 'testFolder';

        $this->connector->expects(self::once())
            ->method('selectFolder')
            ->with($folderName);

        $this->connector->expects(self::once())
            ->method('getUidValidity')
            ->willReturn($uIDValidity);

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $this->createMock(EmailOrigin::class));

        self::assertEquals($uIDValidity, $manager->getUidValidity($folderName));
    }

    public function testTryToGetUidValidityOnWrongParameter(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid argument passed to getUidValidity method.');

        $this->connector->expects(self::never())
            ->method('selectFolder');

        $this->connector->expects(self::never())
            ->method('getUidValidity');

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $this->createMock(EmailOrigin::class));

        $manager->getUidValidity(156);
    }

    public function testTryToGetUidValidityWithExceptionDuringRequest(): void
    {
        $this->connector->expects(self::once())
            ->method('selectFolder')
            ->willThrowException(new \Exception('Error During Request.'));

        $this->connector->expects(self::never())
            ->method('getUidValidity');

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $this->createMock(EmailOrigin::class));

        self::assertNull($manager->getUidValidity('Test'));
    }

    public function testGetFoldersOnNewOrigin(): void
    {
        $origin = new TestEmailOrigin();

        $subFolder = $this->createRemoteFolder('Sub', '[Gmail]\Test\Sub');
        $remoteFolders = [
            $this->createRemoteFolder('Inbox', '[Gmail]\Inbox', ['\Inbox']),
            $this->createRemoteFolder('Test', '[Gmail]\Test', [], false, [$subFolder]),
            $this->createRemoteFolder('Sent', '[Gmail]\Sent', ['\Sent']),
            $this->createRemoteFolder('Spam', '[Gmail]\Spam', ['\Spam']),
            $this->createRemoteFolder('Trash', '[Gmail]\Trash', ['\Trash'])
        ];
        $this->connector->expects(self::once())
            ->method('findFolders')
            ->willReturn($remoteFolders);

        $expectedEmailFolders = new ArrayCollection([
            $this->getEntity(
                EmailFolder::class,
                ['name' => 'Inbox', 'fullName' => '[Gmail]\Inbox', 'type' => 'inbox', 'origin' => $origin]
            ),
            $this->getEntity(
                EmailFolder::class,
                [
                    'name'       => 'Test',
                    'fullName'   => '[Gmail]\Test',
                    'type'       => 'other',
                    'origin'     => $origin,
                    'subFolders' => new ArrayCollection([
                        $this->getEntity(
                            EmailFolder::class,
                            ['name' => 'Sub', 'fullName' => '[Gmail]\Test\Sub', 'type' => 'other', 'origin' => $origin]
                        )
                    ])
                ]
            ),
            $this->getEntity(
                EmailFolder::class,
                ['name' => 'Sent', 'fullName' => '[Gmail]\Sent', 'type' => 'sent', 'origin' => $origin]
            ),
            $this->getEntity(
                EmailFolder::class,
                ['name' => 'Spam', 'fullName' => '[Gmail]\Spam', 'type' => 'spam', 'origin' => $origin]
            ),
            $this->getEntity(
                EmailFolder::class,
                ['name' => 'Trash', 'fullName' => '[Gmail]\Trash', 'type' => 'trash', 'origin' => $origin]
            ),
        ]);

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $origin);

        self::assertEquals($expectedEmailFolders, $manager->getFolders());
    }

    public function testGetFoldersOnOriginWithEmptyFolders(): void
    {
        $origin = new TestEmailOrigin(['id' => 12]);

        $subFolder = $this->createRemoteFolder('Sub', '[Gmail]\Test\Sub');
        $remoteFolders = [
            $this->createRemoteFolder('Inbox', '[Gmail]\Inbox', ['\Inbox']),
            $this->createRemoteFolder('Test', '[Gmail]\Test', [], false, [$subFolder])
        ];
        $this->connector->expects(self::once())
            ->method('findFolders')
            ->willReturn($remoteFolders);

        $this->mockGetExistingFolders([]);

        $expectedEmailFolders = new ArrayCollection([
            $this->getEntity(
                EmailFolder::class,
                ['name' => 'Inbox', 'fullName' => '[Gmail]\Inbox', 'type' => 'inbox', 'origin' => $origin]
            ),
            $this->getEntity(
                EmailFolder::class,
                [
                    'name'       => 'Test',
                    'fullName'   => '[Gmail]\Test',
                    'type'       => 'other',
                    'origin'     => $origin,
                    'subFolders' => new ArrayCollection([
                        $this->getEntity(
                            EmailFolder::class,
                            ['name' => 'Sub', 'fullName' => '[Gmail]\Test\Sub', 'type' => 'other', 'origin' => $origin]
                        )
                    ])
                ]
            )
        ]);

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $origin);
        self::assertEquals($expectedEmailFolders, $manager->getFolders());
    }

    public function testGetFoldersOnOriginWithTheSameFoldersAsAtRemoteServer(): void
    {
        $origin = new TestEmailOrigin(['id' => 12]);

        $subFolder = $this->createRemoteFolder('Sub', '[Gmail]\Test\Sub');
        $remoteFolders = [
            $this->createRemoteFolder('Inbox', '[Gmail]\Inbox', ['\Inbox']),
            $this->createRemoteFolder('Test', '[Gmail]\Test', [], false, [$subFolder])
        ];
        $this->connector->expects(self::once())
            ->method('findFolders')
            ->willReturn($remoteFolders);

        $inboxFolder = $this->getEntity(
            EmailFolder::class,
            ['id' => 1, 'name' => 'Inbox', 'fullName' => '[Gmail]\Inbox', 'type' => 'inbox', 'origin' => $origin]
        );
        $subFolder = $this->getEntity(
            EmailFolder::class,
            ['id' => 2, 'name' => 'Sub', 'fullName' => '[Gmail]\Test\Sub', 'type' => 'other', 'origin' => $origin]
        );
        $testFolder = $this->getEntity(
            EmailFolder::class,
            [
                'name'       => 'Test',
                'fullName'   => '[Gmail]\Test',
                'type'       => 'other',
                'origin'     => $origin,
                'subFolders' => new ArrayCollection([$subFolder])
            ]
        );

        $this->mockGetExistingFolders([
            $this->getEntity(ImapEmailFolder::class, ['uidValidity' => 0, 'folder' => $inboxFolder]),
            $this->getEntity(ImapEmailFolder::class, ['uidValidity' => 0, 'folder' => $subFolder]),
            $this->getEntity(ImapEmailFolder::class, ['uidValidity' => 0, 'folder' => $testFolder])
        ]);

        $expectedEmailFolders = new ArrayCollection([$inboxFolder, $testFolder]);

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $origin);
        self::assertEquals($expectedEmailFolders, $manager->getFolders());
    }

    public function testGetFoldersOnOriginWithNewFoldersAtRemoteServer(): void
    {
        $origin = new TestEmailOrigin(['id' => 12]);

        $subFolder = $this->createRemoteFolder('Sub', '[Gmail]\Test\Sub');
        $remoteFolders = [
            $this->createRemoteFolder('Inbox', '[Gmail]\Inbox', ['\Inbox']),
            $this->createRemoteFolder('New', '[Gmail]\New', []),
            $this->createRemoteFolder('Test', '[Gmail]\Test', [], false, [$subFolder])
        ];
        $this->connector->expects(self::once())
            ->method('findFolders')
            ->willReturn($remoteFolders);

        $inboxFolder = $this->getEntity(
            EmailFolder::class,
            ['id' => 1, 'name' => 'Inbox', 'fullName' => '[Gmail]\Inbox', 'type' => 'inbox', 'origin' => $origin]
        );
        $subFolder = $this->getEntity(
            EmailFolder::class,
            ['id' => 2, 'name' => 'Sub', 'fullName' => '[Gmail]\Test\Sub', 'type' => 'other', 'origin' => $origin]
        );
        $testFolder = $this->getEntity(
            EmailFolder::class,
            [
                'id'         => 3,
                'name'       => 'Test',
                'fullName'   => '[Gmail]\Test',
                'type'       => 'other',
                'origin'     => $origin,
                'subFolders' => new ArrayCollection([$subFolder])
            ]
        );

        $this->mockGetExistingFolders([
            $this->getEntity(ImapEmailFolder::class, ['uidValidity' => 1, 'folder' => $inboxFolder]),
            $this->getEntity(ImapEmailFolder::class, ['uidValidity' => 2, 'folder' => $subFolder]),
            $this->getEntity(ImapEmailFolder::class, ['uidValidity' => 3, 'folder' => $testFolder])
        ]);

        $expectedEmailFolders = new ArrayCollection([
            $inboxFolder,
            $this->getEntity(
                EmailFolder::class,
                ['name' => 'New', 'fullName' => '[Gmail]\New', 'type' => 'other', 'origin' => $origin]
            ),
            $testFolder
        ]);

        $this->connector->expects(self::once())
            ->method('selectFolder')
            ->with('[Gmail]\New');
        $this->connector->expects(self::once())
            ->method('getUidValidity')
            ->willReturn(100);

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $origin);
        self::assertEquals($expectedEmailFolders, $manager->getFolders());
    }

    public function testGetFoldersOnOriginWithNewSubFoldersAtRemoteServer(): void
    {
        $origin = new TestEmailOrigin(['id' => 12]);

        $subFolder = $this->createRemoteFolder('Sub', '[Gmail]\Test\Sub');
        $newSubFolder = $this->createRemoteFolder('NewSub', '[Gmail]\Test\NewSub');
        $remoteFolders = [
            $this->createRemoteFolder('Inbox', '[Gmail]\Inbox', ['\Inbox']),
            $this->createRemoteFolder('Test', '[Gmail]\Test', [], false, [$subFolder, $newSubFolder])
        ];
        $this->connector->expects(self::once())
            ->method('findFolders')
            ->willReturn($remoteFolders);

        $inboxFolder = $this->getEntity(
            EmailFolder::class,
            ['id' => 1, 'name' => 'Inbox', 'fullName' => '[Gmail]\Inbox', 'type' => 'inbox', 'origin' => $origin]
        );
        $subFolder = $this->getEntity(
            EmailFolder::class,
            ['id' => 2, 'name' => 'Sub', 'fullName' => '[Gmail]\Test\Sub', 'type' => 'other', 'origin' => $origin]
        );
        /** @var EmailFolder $testFolder */
        $testFolder = $this->getEntity(
            EmailFolder::class,
            [
                'id'         => 3,
                'name'       => 'Test',
                'fullName'   => '[Gmail]\Test',
                'type'       => 'other',
                'origin'     => $origin,
                'subFolders' => new ArrayCollection([$subFolder])
            ]
        );

        $this->mockGetExistingFolders([
            $this->getEntity(ImapEmailFolder::class, ['uidValidity' => 1, 'folder' => $inboxFolder]),
            $this->getEntity(ImapEmailFolder::class, ['uidValidity' => 2, 'folder' => $subFolder]),
            $this->getEntity(ImapEmailFolder::class, ['uidValidity' => 3, 'folder' => $testFolder])
        ]);

        $expectedEmailFolders = new ArrayCollection([
            $inboxFolder,
            $testFolder
        ]);

        $this->em->expects(self::once())
            ->method('persist');
        $this->connector->expects(self::once())
            ->method('selectFolder')
            ->with('[Gmail]\Test\NewSub');
        $this->connector->expects(self::once())
            ->method('getUidValidity')
            ->willReturn(100);

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $origin);
        self::assertEquals($expectedEmailFolders, $manager->getFolders());
        self::assertEquals(2, $testFolder->getSubFolders()->count());
        self::assertEquals(
            $this->getEntity(
                EmailFolder::class,
                [
                    'name'         => 'NewSub',
                    'fullName'     => '[Gmail]\Test\NewSub',
                    'type'         => 'other',
                    'origin'       => $origin,
                    'parentFolder' => $testFolder
                ]
            ),
            $testFolder->getSubFolders()->toArray()[1]
        );
    }

    public function testGetFoldersOnOriginWithRenamedFolderAtRemoteServer(): void
    {
        $origin = new TestEmailOrigin(['id' => 12]);

        $subFolder = $this->createRemoteFolder('Sub', '[Gmail]\TestRenamed\Sub');
        $remoteFolders = [
            $this->createRemoteFolder('Inbox', '[Gmail]\Inbox', ['\Inbox']),
            $this->createRemoteFolder('TestRenamed', '[Gmail]\TestRenamed', [], false, [$subFolder])
        ];
        $this->connector->expects(self::once())
            ->method('findFolders')
            ->willReturn($remoteFolders);

        $inboxFolder = $this->getEntity(
            EmailFolder::class,
            ['id' => 1, 'name' => 'Inbox', 'fullName' => '[Gmail]\Inbox', 'type' => 'inbox', 'origin' => $origin]
        );
        $subFolder = $this->getEntity(
            EmailFolder::class,
            [
                'id'       => 3,
                'name'     => 'Sub',
                'fullName' => '[Gmail]\Test\Sub',
                'type'     => 'other',
                'origin'   => $origin
            ]
        );

        $testFolder = $this->getEntity(
            EmailFolder::class,
            [
                'id'         => 2,
                'name'       => 'Test',
                'fullName'   => '[Gmail]\Test',
                'type'       => 'other',
                'origin'     => $origin,
                'subFolders' => new ArrayCollection([
                    $subFolder
                ])
            ]
        );

        $this->mockGetExistingFolders([
            $this->getEntity(ImapEmailFolder::class, ['uidValidity' => 1, 'folder' => $inboxFolder]),
            $this->getEntity(ImapEmailFolder::class, ['uidValidity' => 2, 'folder' => $subFolder]),
            $this->getEntity(ImapEmailFolder::class, ['uidValidity' => 3, 'folder' => $testFolder])
        ]);

        $this->em->expects(self::never())
            ->method('persist');
        $this->connector->expects(self::exactly(2))
            ->method('selectFolder');
        $this->connector->expects(self::exactly(2))
            ->method('getUidValidity')
            ->willReturnOnConsecutiveCalls(3, 2);

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $origin);
        self::assertEquals(
            new ArrayCollection([
                $inboxFolder,
                $this->getEntity(
                    EmailFolder::class,
                    [
                        'id'         => 2,
                        'name'       => 'TestRenamed',
                        'fullName'   => '[Gmail]\TestRenamed',
                        'type'       => 'other',
                        'origin'     => $origin,
                        'subFolders' => new ArrayCollection([
                            $this->getEntity(
                                EmailFolder::class,
                                [
                                    'id'       => 3,
                                    'name'     => 'Sub',
                                    'fullName' => '[Gmail]\TestRenamed\Sub',
                                    'type'     => 'other',
                                    'origin'   => $origin
                                ]
                            )
                        ])
                    ]
                )
            ]),
            $manager->getFolders()
        );
    }

    public function testRefreshFoldersOnOriginWithEmptyFolders(): void
    {
        $origin = new TestEmailOrigin(['id' => 15]);

        $this->mockEmRequestsForRefreshFolders([], []);
        $this->connector->expects(self::once())
            ->method('findFolders')
            ->willReturn([]);
        $this->em->expects(self::once())
            ->method('flush');

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $origin);
        $manager->refreshFolders();
    }

    public function testRefreshFoldersSetUIDValidity(): void
    {
        $origin = new TestEmailOrigin(['id' => 16]);

        $inboxEmailFolder = $this->getEntity(
            EmailFolder::class,
            ['id' => 1, 'name' => 'Inbox', 'fullName' => '[Gmail]\Inbox', 'type' => 'inbox', 'origin' => $origin]
        );
        $inboxImapEmailFolder = $this->getEntity(
            ImapEmailFolder::class,
            ['uidValidity' => 0, 'folder' => $inboxEmailFolder]
        );

        $subEmailFolder = $this->getEntity(
            EmailFolder::class,
            ['id' => 2, 'name' => 'Sub', 'fullName' => '[Gmail]\Test\Sub', 'type' => 'other', 'origin' => $origin]
        );
        $subImapEmailFolder = $this->getEntity(
            ImapEmailFolder::class,
            ['uidValidity' => 0, 'folder' => $subEmailFolder]
        );

        $testEmailFolder = $this->getEntity(
            EmailFolder::class,
            [
                'name'       => 'Test',
                'fullName'   => '[Gmail]\Test',
                'type'       => 'other',
                'origin'     => $origin,
                'subFolders' => new ArrayCollection([$subEmailFolder])
            ]
        );
        $testImapEmailFolder = $this->getEntity(
            ImapEmailFolder::class,
            ['uidValidity' => 0, 'folder' => $testEmailFolder]
        );

        $this->mockEmRequestsForRefreshFolders(
            [$inboxImapEmailFolder, $subImapEmailFolder, $testImapEmailFolder],
            []
        );
        $this->connector->expects(self::once())
            ->method('findFolders')
            ->willReturn([]);

        $this->connector->expects(self::exactly(3))
            ->method('selectFolder')
            ->willReturnMap(['[Gmail]\Inbox', '[Gmail]\Test\Sub', '[Gmail]\Test']);

        $this->connector->expects(self::exactly(3))
            ->method('getUidValidity')
            ->willReturnOnConsecutiveCalls(20, 21, 22);

        $this->em->expects(self::exactly(3))
            ->method('persist');

        $this->em->expects(self::exactly(2))
            ->method('flush');

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $origin);
        $manager->refreshFolders();

        self::assertEquals(20, $inboxImapEmailFolder->getUidValidity());
        self::assertEquals(21, $subImapEmailFolder->getUidValidity());
        self::assertEquals(22, $testImapEmailFolder->getUidValidity());
    }

    public function testRefreshFoldersOnEmptyFoldersInOrigin(): void
    {
        $origin = new TestEmailOrigin(['id' => 15]);
        $remoteFolders = [
            $this->createRemoteFolder('Inbox', '[Gmail]\Inbox', ['\Inbox']),
            $this->createRemoteFolder('Sent', '[Gmail]\Sent', ['\Sent']),
            $this->createRemoteFolder('Spam', '[Gmail]\Spam', ['\Spam'])
        ];

        $this->mockEmRequestsForRefreshFolders([], []);

        $this->connector->expects(self::once())
            ->method('findFolders')
            ->willReturn($remoteFolders);

        $this->connector->expects(self::exactly(3))
            ->method('selectFolder');
        $this->connector->expects(self::exactly(3))
            ->method('getUidValidity')
            ->willReturnOnConsecutiveCalls(3, 2, 1);

        $this->em->expects(self::exactly(3))
            ->method('persist');
        $this->em->expects(self::once())
            ->method('flush');

        $manager = new ImapEmailFolderManager($this->connector, $this->em, $origin);
        $manager->refreshFolders();
    }
}
