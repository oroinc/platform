<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGrantedValidator;
use Oro\Component\Testing\Validator\AbstractConstraintValidatorTest;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AccessGrantedValidatorTest extends AbstractConstraintValidatorTest
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $authorizationChecker;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        parent::setUp();
    }

    protected function createValidator()
    {
        return new AccessGrantedValidator($this->authorizationChecker);
    }

    public function testNullIsValid()
    {
        $this->validator->validate(null, new AccessGranted());

        $this->assertNoViolation();
    }

    public function testGranted()
    {
        $constraint = new AccessGranted();
        $entity = new \stdClass();

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($entity))
            ->willReturn(true);

        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }

    public function testDenied()
    {
        $constraint = new AccessGranted();
        $entity = new \stdClass();

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($entity))
            ->willReturn(false);

        $this->validator->validate($entity, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
