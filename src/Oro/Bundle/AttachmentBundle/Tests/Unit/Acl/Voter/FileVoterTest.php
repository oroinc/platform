<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Acl\FileAccessControlChecker;
use Oro\Bundle\AttachmentBundle\Acl\Voter\FileVoter;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileApplicationsProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileVoterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const PARENT_ENTITY_CLASS = \stdClass::class;
    private const PARENT_ENTITY_ID = 1;
    private const PARENT_ENTITY_FIELD_NAME = 'sampleField';
    private const FILE_ID = 10;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var FileAccessControlChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $fileAccessControlChecker;

    /** @var FileApplicationsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fileApplicationsProvider;

    /** @var CurrentApplicationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $currentApplicationProvider;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var FileVoter */
    private $voter;

    /** @var TokenInterface */
    private $token;

    protected function setUp(): void
    {
        $this->currentApplicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);
        $this->fileApplicationsProvider = $this->createMock(FileApplicationsProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->fileAccessControlChecker = $this->createMock(FileAccessControlChecker::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->voter = new FileVoter(
            $this->doctrineHelper,
            $this->fileAccessControlChecker,
            $this->fileApplicationsProvider,
            $this->currentApplicationProvider,
            $this->authorizationChecker,
            $this->tokenAccessor
        );

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
     *
     * @param array $attributes
     * @param object $subject
     * @param int $expectedResult
     */
    public function testVoteWhenUnsupported(array $attributes, $subject, int $expectedResult): void
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
        $this->mockDoctrineHelper();

        $this->doctrineHelper
            ->method('getEntity')
            ->with(File::class, self::FILE_ID)
            ->willReturn(null);

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, $file = new File(), ['VIEW'])
        );
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testVoteWhenNotCoveredByAcl(string $attribute): void
    {
        $this->mockDoctrineHelper();

        $this->doctrineHelper
            ->method('getEntity')
            ->with(File::class, self::FILE_ID)
            ->willReturn($file = new File());

        $this->mockCoveredByAcl($file, false);

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
            ['attribute' => 'DELETE'],
        ];
    }

    private function mockDoctrineHelper(): void
    {
        $this->doctrineHelper
            ->method('getSingleEntityIdentifier')
            ->willReturn(self::FILE_ID);
    }

    private function mockCoveredByAcl(File $file, bool $isCoveredByAcl): void
    {
        $this->fileAccessControlChecker
            ->expects(self::once())
            ->method('isCoveredByAcl')
            ->with($file)
            ->willReturn($isCoveredByAcl);
    }

    public function testVoteWhenNoParentEntityClass(): void
    {
        $this->mockDoctrineHelper();

        $this->doctrineHelper
            ->method('getEntity')
            ->with(File::class, self::FILE_ID)
            ->willReturn($file = new File());

        $this->mockCoveredByAcl($file, true);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $file, ['VIEW'])
        );
    }

    public function testVoteWhenNotAllowedApp(): void
    {
        $this->mockDoctrineHelper();

        $this->doctrineHelper
            ->method('getEntity')
            ->with(File::class, self::FILE_ID)
            ->willReturn($file = $this->getFile());

        $this->mockCoveredByAcl($file, true);

        $this->mockAllowedApps($file, false);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $file, ['VIEW'])
        );
    }

    public function testVoteWhenNoParentEntity(): void
    {
        $this->doctrineHelper
            ->method('getEntity')
            ->willReturnMap([
                [File::class, self::FILE_ID, $file = $this->getFile()],
                [self::PARENT_ENTITY_CLASS, self::PARENT_ENTITY_ID, null],
            ]);

        $this->mockDoctrineHelper();

        $this->mockCoveredByAcl($file, true);

        $this->mockAllowedApps($file, true);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $file, ['VIEW'])
        );
    }

    private function mockAllowedApps(File $file, bool $isAllowed): void
    {
        $this->fileApplicationsProvider
            ->expects(self::once())
            ->method('getFileApplications')
            ->with($file)
            ->willReturn($apps = ['sample_app1', 'sample_app2']);

        $this->currentApplicationProvider
            ->expects(self::once())
            ->method('isApplicationsValid')
            ->with($apps)
            ->willReturn($isAllowed);
    }

    public function testVoteWhenViewOwnFile(): void
    {
        $this->doctrineHelper
            ->method('getEntity')
            ->willReturnMap([
                [File::class, self::FILE_ID, $file = $this->getFile()],
                [self::PARENT_ENTITY_CLASS, self::PARENT_ENTITY_ID, $parentEntity = new \stdClass()],
            ]);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($parentEntity);

        $this->mockDoctrineHelper();

        $this->mockCoveredByAcl($file, true);

        $this->mockAllowedApps($file, true);

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
        $this->mockDoctrineHelper();

        $this->doctrineHelper
            ->method('getEntity')
            ->willReturnMap([
                [File::class, self::FILE_ID, $file = $this->getFile()],
                [self::PARENT_ENTITY_CLASS, self::PARENT_ENTITY_ID, $parentEntity = new \stdClass()],
            ]);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn(new \stdClass());

        $this->mockCoveredByAcl($file = $this->getFile(), true);

        $this->mockAllowedApps($file, true);

        $this->authorizationChecker
            ->expects(self::once())
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
            [
                'isGranted' => true,
                'expectedResult' => VoterInterface::ACCESS_GRANTED,
            ],
            [
                'isGranted' => false,
                'expectedResult' => VoterInterface::ACCESS_DENIED,
            ],
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
