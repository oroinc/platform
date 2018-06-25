<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Provider\ScopeOrganizationCriteriaProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ScopeOrganizationCriteriaProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ScopeOrganizationCriteriaProvider */
    private $provider;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    protected function setUp()
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->provider = new ScopeOrganizationCriteriaProvider($this->tokenStorage);
    }

    public function testGetCriteriaForCurrentScope()
    {
        $organization = new Organization();

        /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject $token */
        $token = $this->createMock(OrganizationContextTokenInterface::class);

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->willReturn($organization);

        $actual = $this->provider->getCriteriaForCurrentScope();
        $this->assertEquals([ScopeOrganizationCriteriaProvider::SCOPE_KEY => $organization], $actual);
    }

    public function testGetCriteriaForCurrentScopeWithoutToken()
    {
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $actual = $this->provider->getCriteriaForCurrentScope();
        $this->assertEquals([], $actual);
    }

    public function testGetCriteriaForCurrentScopeWithoutOrganizationAwareToken()
    {
        /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject $token */
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $actual = $this->provider->getCriteriaForCurrentScope();
        $this->assertEquals([], $actual);
    }

    /**
     * @dataProvider contextDataProvider
     *
     * @param mixed $context
     * @param array $criteria
     */
    public function testGetCriteria($context, array $criteria)
    {
        $actual = $this->provider->getCriteriaByContext($context);
        $this->assertEquals($criteria, $actual);
    }

    /**
     * @return array
     */
    public function contextDataProvider()
    {
        $organization = new Organization();
        $organizationAware = new \stdClass();
        $organizationAware->organization = $organization;

        return [
            'array_context_with_organization_key' => [
                'context' => ['organization' => $organization],
                'criteria' => ['organization' => $organization],
            ],
            'array_context_without_organization_key' => [
                'context' => [],
                'criteria' => [],
            ],
            'object_context_organization_aware' => [
                'context' => $organizationAware,
                'criteria' => ['organization' => $organization],
            ],
            'object_context_not_organization_aware' => [
                'context' => new \stdClass(),
                'criteria' => [],
            ],
        ];
    }
}
