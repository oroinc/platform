<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Validator\UserGridFieldValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserGridFieldValidatorTest extends TestCase
{
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private UserGridFieldValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->validator = new UserGridFieldValidator($this->tokenAccessor, PropertyAccess::createPropertyAccessor());
    }

    public function testHasAccessEditFieldWhenValidatedUserIsCurrentUserAndFieldIsInBlackList(): void
    {
        $currentUser = new User();
        $currentUser->setId(1);
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($currentUser);

        $entity = new User();
        $entity->setId(1);

        self::assertFalse($this->validator->hasAccessEditField($entity, 'enabled'));
    }

    public function testHasAccessEditFieldWhenValidatedUserIsCurrentUserAndFieldIsNotInBlackList(): void
    {
        $currentUser = new User();
        $currentUser->setId(1);
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($currentUser);

        $entity = new User();
        $entity->setId(1);

        self::assertTrue($this->validator->hasAccessEditField($entity, 'email'));
    }

    public function testHasAccessEditFieldWhenValidatedUserIsNotCurrentUser(): void
    {
        $currentUser = new User();
        $currentUser->setId(1);
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($currentUser);

        $entity = new User();
        $entity->setId(2);

        self::assertTrue($this->validator->hasAccessEditField($entity, 'enabled'));
    }
}
