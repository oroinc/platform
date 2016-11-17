<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Provider\ScopeOrganizationCriteriaProvider;
use Oro\Bundle\UserBundle\Entity\User;

class ScopeOrganizationCriteriaProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ScopeOrganizationCriteriaProvider */
    private $provider;

    /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $tokenStorage;

    protected function setUp()
    {
        $this->tokenStorage = $this->getMock(TokenStorageInterface::class);
        $this->provider = new ScopeOrganizationCriteriaProvider($this->tokenStorage);
    }

    public function testGetCriteriaForCurrentScope()
    {
        $organization = new Organization();

        $user = new User();
        $user->setOrganization($organization);

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->getMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

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

    public function testGetCriteriaForCurrentScopeWithoutUser()
    {
        $user = new \stdClass();

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->getMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

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
            'array_context_with_organization_key_invalid_value' => [
                'context' => ['organization' => 123],
                'criteria' => [],
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
