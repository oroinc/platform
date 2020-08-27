<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleOptionMatcher;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AccessRuleOptionMatcherTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var AccessRuleOptionMatcher */
    private $matcher;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->matcher = new AccessRuleOptionMatcher(
            $this->tokenAccessor
        );
    }

    public function testTypeOptionWhenItEqualsToCriteriaType()
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        self::assertTrue(
            $this->matcher->matches($criteria, 'type', 'ORM')
        );
    }

    public function testTypeOptionWhenItDoesNotEqualToCriteriaType()
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        self::assertFalse(
            $this->matcher->matches($criteria, 'type', 'ANOTHER')
        );
    }

    public function testPermissionOptionWhenItEqualsToCriteriaPermission()
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test', 'EDIT');

        self::assertTrue(
            $this->matcher->matches($criteria, 'permission', 'EDIT')
        );
    }

    public function testPermissionOptionWhenItDoesNotEqualToCriteriaPermission()
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test', 'EDIT');

        self::assertFalse(
            $this->matcher->matches($criteria, 'permission', 'ANOTHER')
        );
    }

    public function testEntityClassOptionWhenItEqualsToCriteriaEntityClass()
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        self::assertTrue(
            $this->matcher->matches($criteria, 'entityClass', 'Test\Entity')
        );
    }

    public function testEntityClassOptionWhenItDoesNotEqualToCriteriaEntityClass()
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        self::assertFalse(
            $this->matcher->matches($criteria, 'entityClass', 'Test\Another')
        );
    }

    public function testLoggedUserClassOptionWhenNoLoggedUser()
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        self::assertFalse(
            $this->matcher->matches($criteria, 'loggedUserClass', User::class)
        );
    }

    public function testLoggedUserClassOptionWhenItEqualsToClassOfLoggedUser()
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');
        $user = $this->createMock(User::class);

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        self::assertTrue(
            $this->matcher->matches($criteria, 'loggedUserClass', User::class)
        );
    }

    public function testLoggedUserClassOptionWhenItDoesNotEqualToCriteriaEntityClass()
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');
        $user = $this->createMock(User::class);

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        self::assertFalse(
            $this->matcher->matches($criteria, 'loggedUserClass', UserStub::class)
        );
    }

    public function testOptionWhenItDoesNotExistInCriteria()
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        self::assertFalse(
            $this->matcher->matches($criteria, 'test', true)
        );
    }

    public function testOptionWhenItsValueEqualsToFalseAndItDoesNotExistInCriteria()
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        self::assertTrue(
            $this->matcher->matches($criteria, 'test', false)
        );
    }

    public function testOptionWhenItExistsInCriteriaAndItsValueEqualsToCriteriaOptionValue()
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');
        $criteria->setOption('test', '123');

        self::assertTrue(
            $this->matcher->matches($criteria, 'test', '123')
        );
    }

    public function testOptionWhenItExistsInCriteriaAndItsValueDoesNotEqualToCriteriaOptionValue()
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');
        $criteria->setOption('test', '123');

        self::assertFalse(
            $this->matcher->matches($criteria, 'test', '234')
        );
    }
}
