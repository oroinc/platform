<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Provider\ScopeOrganizationCriteriaProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ScopeOrganizationCriteriaProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var ScopeOrganizationCriteriaProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->provider = new ScopeOrganizationCriteriaProvider($this->tokenStorage);
    }

    public function testGetCriteriaField()
    {
        $this->assertEquals(ScopeOrganizationCriteriaProvider::ORGANIZATION, $this->provider->getCriteriaField());
    }

    public function testGetCriteriaValue()
    {
        $organization = new Organization();

        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $token->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertSame($organization, $this->provider->getCriteriaValue());
    }

    public function testGetCriteriaValueWithoutToken()
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->assertNull($this->provider->getCriteriaValue());
    }

    public function testGetCriteriaValueWithoutOrganizationAwareToken()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertNull($this->provider->getCriteriaValue());
    }

    public function testGetCriteriaValueType()
    {
        $this->assertEquals(Organization::class, $this->provider->getCriteriaValueType());
    }
}
