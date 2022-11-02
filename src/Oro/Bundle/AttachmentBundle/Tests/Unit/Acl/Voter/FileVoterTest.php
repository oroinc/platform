<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Acl\FileAccessControlChecker;
use Oro\Bundle\AttachmentBundle\Acl\Voter\FileVoter;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileApplicationsProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileVoterTest extends \PHPUnit\Framework\TestCase
{
    private const PARENT_ENTITY_CLASS = \stdClass::class;
    private const PARENT_ENTITY_ID = 1;
    private const PARENT_ENTITY_FIELD_NAME = 'sampleField';
    private const FILE_ID = 10;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var FileAccessControlChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $fileAccessControlChecker;

    /** @var FileApplicationsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fileApplicationsProvider;

    /** @var CurrentApplicationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $currentApplicationProvider;

    /** @var TokenInterface */
    private $token;

    /** @var FileVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->fileAccessControlChecker = $this->createMock(FileAccessControlChecker::class);
        $this->fileApplicationsProvider = $this->createMock(FileApplicationsProvider::class);
        $this->currentApplicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);

        $container = TestContainerBuilder::create()
            ->add('oro_attachment.acl.file_access_control_checker', $this->fileAccessControlChecker)
            ->add('oro_attachment.provider.file_applications', $this->fileApplicationsProvider)
            ->add('oro_action.provider.current_application', $this->currentApplicationProvider)
            ->getContainer($this);

        $this->voter = new FileVoter($this->doctrineHelper, $this->authorizationChecker, $container);

        $this->voter->setClassName(File::class);

        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testVoteWhenNoSubject(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, null, ['VIEW'])
        );
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testVoteWhenUnsupported(array $attributes, object $subject, int $expectedResult): void
    {
        self::assertSame(
            $expectedResult,
            $this->voter->vote($this->token, $subject, $attributes)
        );
    }

    public function supportsDataProvider(): array
    {
        return [
            'unsupported class' => [
                'attributes' => ['VIEW'],
                'subject' => new \stdClass(),
                'expectedResult' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'unsupported attribute' => [
                'attributes' => ['UNSUPPORTED_ATTRIBUTE'],
                'subject' => new File(),
                'expectedResult' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'empty attributes' => [
                'attributes' => [],
                'subject' => new File(),
                'expectedResult' => VoterInterface::ACCESS_ABSTAIN,
            ],
        ];
    }

    public function testVoteWhenNotFound(): void
    {
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(self::FILE_ID);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->with(File::class, self::FILE_ID)
            ->willReturn(null);

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new File(), ['VIEW'])
        );
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testVoteWhenNotCoveredByAcl(string $attribute): void
    {
        $file = new File();

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(self::FILE_ID);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->with(File::class, self::FILE_ID)
            ->willReturn($file);

        $this->fileAccessControlChecker->expects(self::once())
            ->method('isCoveredByAcl')
            ->with($file)
            ->willReturn(false);

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, $file, [$attribute])
        );
    }

    public function attributesDataProvider(): array
    {
        return [
            ['attribute' => 'VIEW'],
            ['attribute' => 'EDIT'],
            ['attribute' => 'DELETE']
        ];
    }

    public function testVoteWhenNoParentEntityClass(): void
    {
        $file = new File();

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(self::FILE_ID);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->with(File::class, self::FILE_ID)
            ->willReturn($file);

        $this->fileAccessControlChecker->expects(self::once())
            ->method('isCoveredByAcl')
            ->with($file)
            ->willReturn(true);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $file, ['VIEW'])
        );
    }

    public function testVoteWhenNotAllowedApp(): void
    {
        $file = $this->getFile();
        $allowedApplications = ['sample_app1', 'sample_app2'];

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(self::FILE_ID);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->with(File::class, self::FILE_ID)
            ->willReturn($file);

        $this->fileAccessControlChecker->expects(self::once())
            ->method('isCoveredByAcl')
            ->with($file)
            ->willReturn(true);

        $this->fileApplicationsProvider->expects(self::once())
            ->method('getFileApplications')
            ->with($file)
            ->willReturn($allowedApplications);
        $this->currentApplicationProvider->expects(self::once())
            ->method('isApplicationsValid')
            ->with($allowedApplications)
            ->willReturn(false);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $file, ['VIEW'])
        );
    }

    public function testVoteWhenNoParentEntity(): void
    {
        $file = $this->getFile();
        $allowedApplications = ['sample_app1', 'sample_app2'];

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntity')
            ->willReturnMap([
                [File::class, self::FILE_ID, $file],
                [self::PARENT_ENTITY_CLASS, self::PARENT_ENTITY_ID, null],
            ]);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(self::FILE_ID);

        $this->fileAccessControlChecker->expects(self::once())
            ->method('isCoveredByAcl')
            ->with($file)
            ->willReturn(true);

        $this->fileApplicationsProvider->expects(self::once())
            ->method('getFileApplications')
            ->with($file)
            ->willReturn($allowedApplications);
        $this->currentApplicationProvider->expects(self::once())
            ->method('isApplicationsValid')
            ->with($allowedApplications)
            ->willReturn(true);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $file, ['VIEW'])
        );
    }

    public function testVoteWhenViewOwnFile(): void
    {
        $file = $this->getFile();
        $allowedApplications = ['sample_app1', 'sample_app2'];

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntity')
            ->willReturnMap([
                [File::class, self::FILE_ID, $file],
                [self::PARENT_ENTITY_CLASS, self::PARENT_ENTITY_ID, $parentEntity = new \stdClass()],
            ]);

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($parentEntity);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(self::FILE_ID);

        $this->fileAccessControlChecker->expects(self::once())
            ->method('isCoveredByAcl')
            ->with($file)
            ->willReturn(true);

        $this->fileApplicationsProvider->expects(self::once())
            ->method('getFileApplications')
            ->with($file)
            ->willReturn($allowedApplications);
        $this->currentApplicationProvider->expects(self::once())
            ->method('isApplicationsValid')
            ->with($allowedApplications)
            ->willReturn(true);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token, $file, ['VIEW'])
        );
    }

    /**
     * @dataProvider voteDataProvider
     */
    public function testVote(bool $isGranted, int $expectedResult): void
    {
        $file = $this->getFile();
        $allowedApplications = ['sample_app1', 'sample_app2'];

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(self::FILE_ID);

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntity')
            ->willReturnMap([
                [File::class, self::FILE_ID, $file],
                [self::PARENT_ENTITY_CLASS, self::PARENT_ENTITY_ID, $parentEntity = new \stdClass()],
            ]);

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn(new \stdClass());

        $this->fileAccessControlChecker->expects(self::once())
            ->method('isCoveredByAcl')
            ->with($file)
            ->willReturn(true);

        $this->fileApplicationsProvider->expects(self::once())
            ->method('getFileApplications')
            ->with($file)
            ->willReturn($allowedApplications);
        $this->currentApplicationProvider->expects(self::once())
            ->method('isApplicationsValid')
            ->with($allowedApplications)
            ->willReturn(true);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', $parentEntity)
            ->willReturn($isGranted);

        self::assertSame(
            $expectedResult,
            $this->voter->vote($this->token, $file, ['VIEW'])
        );
    }

    public function voteDataProvider(): array
    {
        return [
            ['isGranted' => true, 'expectedResult' => VoterInterface::ACCESS_GRANTED],
            ['isGranted' => false, 'expectedResult' => VoterInterface::ACCESS_DENIED]
        ];
    }

    private function getFile(): File
    {
        $file = new File();
        $file->setParentEntityClass(self::PARENT_ENTITY_CLASS);
        $file->setParentEntityId(self::PARENT_ENTITY_ID);
        $file->setParentEntityFieldName(self::PARENT_ENTITY_FIELD_NAME);
        $file->setUuid('test-uuid');

        return $file;
    }
}
