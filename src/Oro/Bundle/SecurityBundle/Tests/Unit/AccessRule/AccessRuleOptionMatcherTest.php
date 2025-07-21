<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleOptionMatcher;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AccessRuleOptionMatcherTest extends TestCase
{
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private AccessRuleOptionMatcher $matcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->matcher = new AccessRuleOptionMatcher(
            $this->tokenAccessor
        );
    }

    public function testTypeOptionWhenItEqualsToCriteriaType(): void
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        self::assertTrue(
            $this->matcher->matches($criteria, 'type', 'ORM')
        );
    }

    public function testTypeOptionWhenItDoesNotEqualToCriteriaType(): void
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        self::assertFalse(
            $this->matcher->matches($criteria, 'type', 'ANOTHER')
        );
    }

    public function testPermissionOptionWhenItEqualsToCriteriaPermission(): void
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test', 'EDIT');

        self::assertTrue(
            $this->matcher->matches($criteria, 'permission', 'EDIT')
        );
    }

    public function testPermissionOptionWhenItDoesNotEqualToCriteriaPermission(): void
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test', 'EDIT');

        self::assertFalse(
            $this->matcher->matches($criteria, 'permission', 'ANOTHER')
        );
    }

    public function testEntityClassOptionWhenItEqualsToCriteriaEntityClass(): void
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        self::assertTrue(
            $this->matcher->matches($criteria, 'entityClass', 'Test\Entity')
        );
    }

    public function testEntityClassOptionWhenItDoesNotEqualToCriteriaEntityClass(): void
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        self::assertFalse(
            $this->matcher->matches($criteria, 'entityClass', 'Test\Another')
        );
    }

    public function testLoggedUserClassOptionWhenNoLoggedUser(): void
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        self::assertFalse(
            $this->matcher->matches($criteria, 'loggedUserClass', User::class)
        );
    }

    public function testLoggedUserClassOptionWhenItEqualsToClassOfLoggedUser(): void
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

    public function testLoggedUserClassOptionWhenItDoesNotEqualToCriteriaEntityClass(): void
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

    public function testOptionWhenItDoesNotExistInCriteria(): void
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        self::assertFalse(
            $this->matcher->matches($criteria, 'test', true)
        );
    }

    public function testOptionWhenItsValueEqualsToFalseAndItDoesNotExistInCriteria(): void
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');

        self::assertTrue(
            $this->matcher->matches($criteria, 'test', false)
        );
    }

    public function testOptionWhenItExistsInCriteriaAndItsValueEqualsToCriteriaOptionValue(): void
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');
        $criteria->setOption('test', '123');

        self::assertTrue(
            $this->matcher->matches($criteria, 'test', '123')
        );
    }

    public function testOptionWhenItExistsInCriteriaAndItsValueDoesNotEqualToCriteriaOptionValue(): void
    {
        $criteria = new Criteria('ORM', 'Test\Entity', 'test');
        $criteria->setOption('test', '123');

        self::assertFalse(
            $this->matcher->matches($criteria, 'test', '234')
        );
    }
}
