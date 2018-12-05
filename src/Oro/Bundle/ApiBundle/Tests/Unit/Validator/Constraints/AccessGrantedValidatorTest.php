<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGrantedValidator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AccessGrantedValidatorTest extends ConstraintValidatorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    private $authorizationChecker;

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

    public function testGrantedForDefaultPermission()
    {
        $constraint = new AccessGranted();
        $entity = new \stdClass();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($entity))
            ->willReturn(true);

        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }

    public function testGrantedForCustomPermission()
    {
        $constraint = new AccessGranted(['permission' => 'EDIT']);
        $entity = new \stdClass();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('EDIT', self::identicalTo($entity))
            ->willReturn(true);

        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }

    public function testDeniedForDefaultPermission()
    {
        $constraint = new AccessGranted();
        $entity = new \stdClass();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($entity))
            ->willReturn(false);

        $this->validator->validate($entity, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ permission }}', 'VIEW')
            ->assertRaised();
    }

    public function testDeniedForCustomPermission()
    {
        $constraint = new AccessGranted(['permission' => 'EDIT']);
        $entity = new \stdClass();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('EDIT', self::identicalTo($entity))
            ->willReturn(false);

        $this->validator->validate($entity, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ permission }}', 'EDIT')
            ->assertRaised();
    }
}
