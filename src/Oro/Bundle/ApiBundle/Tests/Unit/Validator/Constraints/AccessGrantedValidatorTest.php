<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGrantedValidator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AccessGrantedValidatorTest extends ConstraintValidatorTestCase
{
    private \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface $authorizationChecker;
    private \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper $doctrineHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): AccessGrantedValidator
    {
        return new AccessGrantedValidator($this->authorizationChecker, $this->doctrineHelper);
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new AccessGranted());

        $this->assertNoViolation();
    }

    public function testGrantedForDefaultPermission(): void
    {
        $constraint = new AccessGranted();
        $entity = new \stdClass();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($entity))
            ->willReturn(true);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with(self::identicalTo($entity))
            ->willReturn(2);

        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }

    public function testGrantedForCustomPermission(): void
    {
        $constraint = new AccessGranted(['permission' => 'EDIT']);
        $entity = new \stdClass();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('EDIT', self::identicalTo($entity))
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with(self::identicalTo($entity))
            ->willReturn(2);

        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }

    public function testDeniedForDefaultPermission(): void
    {
        $constraint = new AccessGranted();
        $entity = new \stdClass();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($entity))
            ->willReturn(false);
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with(self::identicalTo($entity))
            ->willReturn(2);

        $this->validator->validate($entity, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ permission }}', 'VIEW')
            ->assertRaised();
    }

    public function testDeniedForCustomPermission(): void
    {
        $constraint = new AccessGranted(['permission' => 'EDIT']);
        $entity = new \stdClass();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('EDIT', self::identicalTo($entity))
            ->willReturn(false);
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with(self::identicalTo($entity))
            ->willReturn(2);

        $this->validator->validate($entity, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ permission }}', 'EDIT')
            ->assertRaised();
    }

    public function testGrantedForNewEntity(): void
    {
        $constraint = new AccessGranted();
        $entity = new \stdClass();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, \stdClass::class))
            ->willReturn(true);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with(self::identicalTo($entity))
            ->willReturn(null);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityClass')
            ->with(self::identicalTo($entity))
            ->willReturn(\stdClass::class);

        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }
}
