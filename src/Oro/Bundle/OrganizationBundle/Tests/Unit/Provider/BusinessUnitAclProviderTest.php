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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class BusinessUnitAclProviderTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_NAME = 'test';
    private const PERMISSION  = 'VIEW';

    /** @var BusinessUnitAclProvider */
    private $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $aclVoter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $treeProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $tree;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $user;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $organization;

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

    public function testSystemLevel()
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

    public function testLocalLevel()
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

    public function testDeepLevel()
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

    public function testGlobalLevel()
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

    public function testAccessNotGranted()
    {
        $this->treeProvider->expects(self::never())
            ->method('getTree');

        self::assertEquals(
            [],
            $this->provider->getBusinessUnitIds(self::ENTITY_NAME, self::PERMISSION)
        );
    }
}
