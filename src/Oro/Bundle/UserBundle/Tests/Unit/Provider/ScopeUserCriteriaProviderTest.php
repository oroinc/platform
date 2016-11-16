<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\ScopeUserCriteriaProvider;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ScopeUserCriteriaProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ScopeUserCriteriaProvider */
    private $provider;

    /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $tokenStorage;

    protected function setUp()
    {
        $this->tokenStorage = $this->getMock(TokenStorageInterface::class);
        $this->provider = new ScopeUserCriteriaProvider($this->tokenStorage);
    }

    public function testGetCriteriaForCurrentScope()
    {
        $user = new User();

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
        $this->assertEquals([ScopeUserCriteriaProvider::SCOPE_KEY => $user], $actual);
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
        $user = new User();
        $userAware = new \stdClass();
        $userAware->user = $user;

        return [
            'array_context_with_user_key' => [
                'context' => ['user' => $user],
                'criteria' => ['user' => $user],
            ],
            'array_context_with_user_key_invalid_value' => [
                'context' => ['user' => $user],
                'criteria' => [],
            ],
            'array_context_without_user_key' => [
                'context' => [],
                'criteria' => [],
            ],
            'object_context_useraware' => [
                'context' => $userAware,
                'criteria' => ['user' => $user],
            ],
            'object_context_not_user_aware' => [
                'context' => new \stdClass(),
                'criteria' => [],
            ],
        ];
    }
}
