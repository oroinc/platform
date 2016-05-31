<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Tests\Constraints\AbstractConstraintValidatorTest;

use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGrantedValidator;

class AccessGrantedValidatorTest extends AbstractConstraintValidatorTest
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    protected function createValidator()
    {
        return new AccessGrantedValidator($this->securityFacade);
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

        $this->securityFacade->expects($this->once())
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

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($entity))
            ->willReturn(false);

        $this->validator->validate($entity, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
