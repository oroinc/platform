<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ScopeBundle\Manager\ScopeCacheKeyBuilderInterface;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\UserScopeCacheKeyBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserScopeCacheKeyBuilderTest extends \PHPUnit\Framework\TestCase
{
    private function getInnerBuilder(ScopeCriteria $criteria, ?string $cacheKey): ScopeCacheKeyBuilderInterface
    {
        $innerBuilder = $this->createMock(ScopeCacheKeyBuilderInterface::class);
        $innerBuilder->expects($this->any())
            ->method('getCacheKey')
            ->with($this->identicalTo($criteria))
            ->willReturn($cacheKey);

        return $innerBuilder;
    }

    public function testGetCacheKeyWhenNoToken()
    {
        $criteria = $this->createMock(ScopeCriteria::class);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $builder = new UserScopeCacheKeyBuilder(
            $this->getInnerBuilder($criteria, 'data'),
            $tokenStorage
        );
        $this->assertEquals('data', $builder->getCacheKey($criteria));
    }

    public function testGetCacheKeyForUnsupportedUserType()
    {
        $criteria = $this->createMock(ScopeCriteria::class);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn('test');

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $builder = new UserScopeCacheKeyBuilder(
            $this->getInnerBuilder($criteria, 'data'),
            $tokenStorage
        );
        $this->assertEquals('data', $builder->getCacheKey($criteria));
    }

    public function testGetCacheKeyForUser()
    {
        $criteria = $this->createMock(ScopeCriteria::class);

        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $organization = $this->createMock(Organization::class);
        $organization->expects($this->once())
            ->method('getId')
            ->willReturn(100);

        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $token->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $builder = new UserScopeCacheKeyBuilder(
            $this->getInnerBuilder($criteria, 'data'),
            $tokenStorage
        );
        $this->assertEquals('data;user=1;organization=100', $builder->getCacheKey($criteria));
    }

    public function testGetCacheKeyForUserWithoutOrganization()
    {
        $criteria = $this->createMock(ScopeCriteria::class);

        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $builder = new UserScopeCacheKeyBuilder(
            $this->getInnerBuilder($criteria, 'data'),
            $tokenStorage
        );
        $this->assertEquals('data;user=1', $builder->getCacheKey($criteria));
    }
}
