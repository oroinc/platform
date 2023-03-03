<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Validator\UserGridFieldValidator;

class UserGridFieldValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var UserGridFieldValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->validator = new UserGridFieldValidator($this->tokenAccessor, PropertyAccess::createPropertyAccessor());
    }

    public function testHasAccessEditFieldWhenValidatedUserIsCurrentUserAndFieldIsInBlackList()
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

    public function testHasAccessEditFieldWhenValidatedUserIsCurrentUserAndFieldIsNotInBlackList()
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

    public function testHasAccessEditFieldWhenValidatedUserIsNotCurrentUser()
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
