<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Voter;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Helper\DraftPermissionHelper;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\DraftBundle\Voter\BasicPermissionsDraftVoter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class BasicPermissionsDraftVoterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public static $user;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var BasicPermissionsDraftVoter */
    private $voter;

    protected function setUp(): void
    {
        $tokenAccessor = $this->createMock(TokenAccessor::class);
        $tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn(self::$user);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturn(1);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturn(DraftableEntityStub::class);

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $container = TestContainerBuilder::create()
            ->add('oro_draft.helper.draft_permission_helper', new DraftPermissionHelper($tokenAccessor))
            ->getContainer($this);

        $this->voter = new BasicPermissionsDraftVoter($this->doctrineHelper, $this->authorizationChecker, $container);
    }

    /**
     * @dataProvider permissionDataProvider
     */
    public function testGetPermissionForAttribute(int $expected, array $permissions, DraftableInterface $source): void
    {
        $token = $this->createMock(TokenInterface::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntity')
            ->willReturn($source);
        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturn(false);

        $isGranted = $this->voter->vote($token, $source, $permissions);
        $this->assertEquals($expected, $isGranted);
    }

    public function permissionDataProvider(): array
    {
        self::$user = new User();
        $draftSource = new DraftableEntityStub();

        return [
            'Original entity with VIEW permission' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'permissions' => ['VIEW'],
                'source' => $this->getEntity(DraftableEntityStub::class)
            ],
            'Original entity with EDIT permission' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'permissions' => ['EDIT'],
                'source' => $this->getEntity(DraftableEntityStub::class)
            ],
            'Original entity with DELETE permission' => [
                'expected' => VoterInterface::ACCESS_ABSTAIN,
                'permissions' => ['DELETE'],
                'source' => $this->getEntity(DraftableEntityStub::class)
            ],
            'Draft entity with VIEW permission' => [
                'expected' => VoterInterface::ACCESS_DENIED,
                'permissions' => ['VIEW'],
                'source' => $this->getEntity(
                    DraftableEntityStub::class,
                    ['draftUuid' => UUIDGenerator::v4()]
                )
            ],
            'Draft entity with EDIT permission' => [
                'expected' => VoterInterface::ACCESS_DENIED,
                'permissions' => ['EDIT'],
                'source' => $this->getEntity(
                    DraftableEntityStub::class,
                    ['draftUuid' => UUIDGenerator::v4()]
                )
            ],
            'Draft entity with DELETE permission' => [
                'expected' => VoterInterface::ACCESS_DENIED,
                'permissions' => ['DELETE'],
                'source' => $this->getEntity(
                    DraftableEntityStub::class,
                    ['draftUuid' => UUIDGenerator::v4()]
                )
            ],
            'Draft entity with VIEW permission and user owner' => [
                'expected' => VoterInterface::ACCESS_GRANTED,
                'permissions' => ['VIEW'],
                'source' => $this->getEntity(
                    DraftableEntityStub::class,
                    ['draftUuid' => UUIDGenerator::v4(), 'draftOwner' => self::$user]
                )
            ],
            'Draft entity with EDIT permission and user owner' => [
                'expected' => VoterInterface::ACCESS_GRANTED,
                'permissions' => ['EDIT'],
                'source' => $this->getEntity(
                    DraftableEntityStub::class,
                    ['draftUuid' => UUIDGenerator::v4(), 'draftOwner' => self::$user]
                )
            ],
            'Draft entity with DELETE permission and user owner' => [
                'expected' => VoterInterface::ACCESS_DENIED,
                'permissions' => ['DELETE'],
                'source' => $this->getEntity(
                    DraftableEntityStub::class,
                    ['draftUuid' => UUIDGenerator::v4(), 'draftOwner' => self::$user]
                )
            ],
            'Draft entity with Create permission and user owner' => [
                'expected' => VoterInterface::ACCESS_DENIED,
                'permissions' => ['CREATE_DRAFT'],
                'source' => $this->getEntity(
                    DraftableEntityStub::class,
                    ['draftUuid' => UUIDGenerator::v4(), 'draftOwner' => self::$user, 'draftSource' => $draftSource]
                )
            ],
            'Draft entity with Publish permission and user owner' => [
                'expected' => VoterInterface::ACCESS_DENIED,
                'permissions' => ['PUBLISH_DRAFT'],
                'source' => $this->getEntity(
                    DraftableEntityStub::class,
                    ['draftUuid' => UUIDGenerator::v4(), 'draftOwner' => self::$user, 'draftSource' => $draftSource]
                )
            ],
        ];
    }
}
