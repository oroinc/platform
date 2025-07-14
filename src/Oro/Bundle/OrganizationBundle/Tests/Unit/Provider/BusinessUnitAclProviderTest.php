<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Provider\BusinessUnitAclProvider;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class BusinessUnitAclProviderTest extends TestCase
{
    private const ENTITY_NAME = 'test';
    private const PERMISSION = 'VIEW';

    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private AclVoter&MockObject $aclVoter;
    private OwnerTreeProvider&MockObject $treeProvider;
    private OwnerTreeInterface&MockObject $tree;
    private User&MockObject $user;
    private Organization&MockObject $organization;
    private BusinessUnitAclProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->aclVoter = $this->createMock(AclVoter::class);
        $this->treeProvider = $this->createMock(OwnerTreeProvider::class);
        $this->tree = $this->createMock(OwnerTreeInterface::class);
        $this->user = $this->createMock(User::class);
        $this->organization = $this->createMock(Organization::class);

        $this->tokenAccessor->expects(self::any())
            ->method('getUser')
            ->willReturn($this->user);

        $this->tokenAccessor->expects(self::any())
            ->method('getOrganization')
            ->willReturn($this->organization);

        $this->provider = new BusinessUnitAclProvider(
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->aclVoter,
            $this->treeProvider
        );
    }

    /**
     * @param int $returnedAccessLevel
     */
    private function expectIsGranted($returnedAccessLevel)
    {
        /** @var OneShotIsGrantedObserver $observer */
        $observer = null;
        $this->aclVoter->expects(self::once())
            ->method('addOneShotIsGrantedObserver')
            ->willReturnCallback(function (OneShotIsGrantedObserver $o) use (&$observer) {
                $observer = $o;
            });
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(self::PERMISSION, 'entity:' . self::ENTITY_NAME)
            ->willReturnCallback(function () use (&$observer, $returnedAccessLevel) {
                $observer->setAccessLevel($returnedAccessLevel);

                return true;
            });
    }

    public function testSystemLevel(): void
    {
        $ids = [1, 2];

        $this->expectIsGranted(AccessLevel::SYSTEM_LEVEL);

        $this->treeProvider->expects(self::once())
            ->method('getTree')
            ->willReturn($this->tree);

        $this->tree->expects(self::once())
            ->method('getAllBusinessUnitIds')
            ->willReturn($ids);

        self::assertEquals(
            $ids,
            $this->provider->getBusinessUnitIds(self::ENTITY_NAME, self::PERMISSION)
        );
    }

    public function testLocalLevel(): void
    {
        $ids = [1, 2];

        $this->expectIsGranted(AccessLevel::LOCAL_LEVEL);

        $this->treeProvider->expects(self::once())
            ->method('getTree')
            ->willReturn($this->tree);

        $this->tree->expects(self::once())
            ->method('getUserBusinessUnitIds')
            ->willReturn($ids);

        self::assertEquals(
            $ids,
            $this->provider->getBusinessUnitIds(self::ENTITY_NAME, self::PERMISSION)
        );
    }

    public function testDeepLevel(): void
    {
        $ids = [1, 2];

        $this->expectIsGranted(AccessLevel::DEEP_LEVEL);

        $this->treeProvider->expects(self::once())
            ->method('getTree')
            ->willReturn($this->tree);

        $this->tree->expects(self::once())
            ->method('getUserSubordinateBusinessUnitIds')
            ->willReturn($ids);

        self::assertEquals(
            $ids,
            $this->provider->getBusinessUnitIds(self::ENTITY_NAME, self::PERMISSION)
        );
    }

    public function testGlobalLevel(): void
    {
        $ids = [1, 2];

        $this->expectIsGranted(AccessLevel::GLOBAL_LEVEL);

        $this->treeProvider->expects(self::once())
            ->method('getTree')
            ->willReturn($this->tree);

        $this->tree->expects(self::once())
            ->method('getOrganizationBusinessUnitIds')
            ->willReturn($ids);

        self::assertEquals(
            $ids,
            $this->provider->getBusinessUnitIds(self::ENTITY_NAME, self::PERMISSION)
        );
    }

    public function testAccessNotGranted(): void
    {
        $this->treeProvider->expects(self::never())
            ->method('getTree');

        self::assertEquals(
            [],
            $this->provider->getBusinessUnitIds(self::ENTITY_NAME, self::PERMISSION)
        );
    }
}
