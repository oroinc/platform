<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig;

use Oro\Bundle\EmailBundle\Twig\EmailTemplateEntityAccessChecker;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

final class EmailTemplateEntityAccessCheckerTest extends TestCase
{
    private const int CURRENT_ORGANIZATION_ID = 42;
    private const int RECORD_ID = 101;
    private const int USER_ID = 1;
    private const int OTHER_USER_ID = 2;

    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private DoctrineHelper&MockObject $doctrineHelper;
    private EmailTemplateEntityAccessChecker $entityAccessChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->entityAccessChecker = new EmailTemplateEntityAccessChecker(
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->doctrineHelper
        );
    }

    public function testAssertPropertyAccessGrantedAllowsNonObject(): void
    {
        $this->tokenAccessor
            ->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        $this->entityAccessChecker->assertPropertyAccessGranted('not an object', 'name');
    }

    public function testAssertMethodAccessGrantedAllowsNonObject(): void
    {
        $this->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        $this->entityAccessChecker->assertMethodAccessGranted(null, 'getName');
    }

    public function testAssertPropertyAccessGrantedAllowsEverythingWhenTokenHoldsNoUser(): void
    {
        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $this->doctrineHelper
            ->expects(self::never())
            ->method('isManageableEntity');

        $this->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        $this->entityAccessChecker->assertPropertyAccessGranted($this->createRecord(self::RECORD_ID), 'name');
    }

    public function testAssertMethodAccessGrantedAllowsEverythingWhenTokenHoldsNoUser(): void
    {
        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $this->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        $this->entityAccessChecker->assertMethodAccessGranted($this->createRecord(self::RECORD_ID), 'getName');
    }

    public function testAssertPropertyAccessGrantedAllowsNonManageableObject(): void
    {
        $object = new \stdClass();
        $user = new User();
        ReflectionUtil::setId($user, self::USER_ID);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('isManageableEntity')
            ->with($object)
            ->willReturn(false);

        $this->doctrineHelper
            ->expects(self::never())
            ->method('isNewEntity');

        $this->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        $this->entityAccessChecker->assertPropertyAccessGranted($object, 'name');
    }

    public function testAssertMethodAccessGrantedAllowsNonManageableObject(): void
    {
        $object = new \stdClass();
        $user = new User();
        ReflectionUtil::setId($user, self::USER_ID);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('isManageableEntity')
            ->with($object)
            ->willReturn(false);

        $this->doctrineHelper
            ->expects(self::never())
            ->method('isNewEntity');

        $this->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        $this->entityAccessChecker->assertMethodAccessGranted($object, 'getName');
    }

    public function testAssertPropertyAccessGrantedAllowsNewEntity(): void
    {
        $record = new Organization();
        $user = new User();
        ReflectionUtil::setId($user, self::USER_ID);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('isManageableEntity')
            ->with($record)
            ->willReturn(true);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($record)
            ->willReturn(true);

        $this->doctrineHelper
            ->expects(self::never())
            ->method('getSingleEntityIdentifier');

        $this->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        $this->entityAccessChecker->assertPropertyAccessGranted($record, 'name');
    }

    public function testAssertMethodAccessGrantedAllowsNewEntity(): void
    {
        $record = new Organization();
        $user = new User();
        ReflectionUtil::setId($user, self::USER_ID);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('isManageableEntity')
            ->with($record)
            ->willReturn(true);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($record)
            ->willReturn(true);

        $this->doctrineHelper
            ->expects(self::never())
            ->method('getSingleEntityIdentifier');

        $this->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        $this->entityAccessChecker->assertMethodAccessGranted($record, 'getName');
    }

    public function testAssertPropertyAccessGrantedAllowsWhenViewIsGranted(): void
    {
        $record = $this->createRecord(self::RECORD_ID);
        $user = new User();
        ReflectionUtil::setId($user, self::USER_ID);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor
            ->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(self::CURRENT_ORGANIZATION_ID);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('isManageableEntity')
            ->with($record)
            ->willReturn(true);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($record)
            ->willReturn(false);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($record, false)
            ->willReturn(self::RECORD_ID);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityClass')
            ->with($record)
            ->willReturn(Organization::class);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $record)
            ->willReturn(true);

        $this->entityAccessChecker->assertPropertyAccessGranted($record, 'name');
    }

    public function testAssertMethodAccessGrantedAllowsWhenViewIsGranted(): void
    {
        $record = $this->createRecord(self::RECORD_ID);
        $user = new User();
        ReflectionUtil::setId($user, self::USER_ID);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor
            ->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(self::CURRENT_ORGANIZATION_ID);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('isManageableEntity')
            ->with($record)
            ->willReturn(true);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($record)
            ->willReturn(false);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($record, false)
            ->willReturn(self::RECORD_ID);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityClass')
            ->with($record)
            ->willReturn(Organization::class);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $record)
            ->willReturn(true);

        $this->entityAccessChecker->assertMethodAccessGranted($record, 'getName');
    }

    public function testAssertPropertyAccessGrantedThrowsWhenViewIsNotGranted(): void
    {
        $record = $this->createRecord(self::RECORD_ID);
        $user = new User();
        ReflectionUtil::setId($user, self::USER_ID);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor
            ->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(self::CURRENT_ORGANIZATION_ID);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('isManageableEntity')
            ->with($record)
            ->willReturn(true);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($record)
            ->willReturn(false);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($record, false)
            ->willReturn(self::RECORD_ID);
        // Once for the memo key, once more to build the access denied error.
        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getEntityClass')
            ->with($record)
            ->willReturn(Organization::class);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $record)
            ->willReturn(false);

        try {
            $this->entityAccessChecker->assertPropertyAccessGranted($record, 'name');
            self::fail(sprintf('Failed asserting that %s is thrown.', SecurityNotAllowedPropertyError::class));
        } catch (SecurityNotAllowedPropertyError $error) {
            self::assertSame(
                sprintf('Access to the "%s" record is not allowed for the current user.', Organization::class),
                $error->getMessage()
            );
            self::assertSame(Organization::class, $error->getClassName());
            self::assertSame('name', $error->getPropertyName());
        }
    }

    public function testAssertMethodAccessGrantedThrowsWhenViewIsNotGranted(): void
    {
        $record = $this->createRecord(self::RECORD_ID);
        $user = new User();
        ReflectionUtil::setId($user, self::USER_ID);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor
            ->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(self::CURRENT_ORGANIZATION_ID);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('isManageableEntity')
            ->with($record)
            ->willReturn(true);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($record)
            ->willReturn(false);
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($record, false)
            ->willReturn(self::RECORD_ID);
        // Once for the memo key, once more to build the access denied error.
        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getEntityClass')
            ->with($record)
            ->willReturn(Organization::class);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $record)
            ->willReturn(false);

        try {
            $this->entityAccessChecker->assertMethodAccessGranted($record, '__toString');
            self::fail(sprintf('Failed asserting that %s is thrown.', SecurityNotAllowedMethodError::class));
        } catch (SecurityNotAllowedMethodError $error) {
            self::assertSame(
                sprintf('Access to the "%s" record is not allowed for the current user.', Organization::class),
                $error->getMessage()
            );
            self::assertSame(Organization::class, $error->getClassName());
            self::assertSame('__toString', $error->getMethodName());
        }
    }

    public function testAccessDecisionIsMemoizedForTheSameRecordAndUser(): void
    {
        $record = $this->createRecord(self::RECORD_ID);
        $user = new User();
        ReflectionUtil::setId($user, self::USER_ID);

        // Every one of the three accesses walks the flow down to the memo key, only the decision itself is reused.
        $this->tokenAccessor
            ->expects(self::exactly(3))
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor
            ->expects(self::exactly(3))
            ->method('getOrganizationId')
            ->willReturn(self::CURRENT_ORGANIZATION_ID);

        $this->doctrineHelper
            ->expects(self::exactly(3))
            ->method('isManageableEntity')
            ->with($record)
            ->willReturn(true);
        $this->doctrineHelper
            ->expects(self::exactly(3))
            ->method('isNewEntity')
            ->with($record)
            ->willReturn(false);
        $this->doctrineHelper
            ->expects(self::exactly(3))
            ->method('getSingleEntityIdentifier')
            ->with($record, false)
            ->willReturn(self::RECORD_ID);
        $this->doctrineHelper
            ->expects(self::exactly(3))
            ->method('getEntityClass')
            ->with($record)
            ->willReturn(Organization::class);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $record)
            ->willReturn(true);

        $this->entityAccessChecker->assertPropertyAccessGranted($record, 'name');
        $this->entityAccessChecker->assertPropertyAccessGranted($record, 'description');
        $this->entityAccessChecker->assertMethodAccessGranted($record, 'getName');
    }

    public function testAccessDecisionIsNotMemoizedAcrossUsers(): void
    {
        $record = $this->createRecord(self::RECORD_ID);

        $firstUser = new User();
        ReflectionUtil::setId($firstUser, self::USER_ID);
        $secondUser = new User();
        ReflectionUtil::setId($secondUser, self::OTHER_USER_ID);

        $this->tokenAccessor
            ->expects(self::exactly(2))
            ->method('getUser')
            ->willReturnOnConsecutiveCalls($firstUser, $secondUser);
        $this->tokenAccessor
            ->expects(self::exactly(2))
            ->method('getOrganizationId')
            ->willReturn(self::CURRENT_ORGANIZATION_ID);

        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('isManageableEntity')
            ->with($record)
            ->willReturn(true);
        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('isNewEntity')
            ->with($record)
            ->willReturn(false);
        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getSingleEntityIdentifier')
            ->with($record, false)
            ->willReturn(self::RECORD_ID);
        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getEntityClass')
            ->with($record)
            ->willReturn(Organization::class);

        $this->authorizationChecker
            ->expects(self::exactly(2))
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $record)
            ->willReturn(true);

        $this->entityAccessChecker->assertPropertyAccessGranted($record, 'name');
        $this->entityAccessChecker->assertPropertyAccessGranted($record, 'name');
    }

    public function testAccessDecisionIsNotMemoizedForRecordWithCompositeIdentifier(): void
    {
        $record = $this->createRecord(self::RECORD_ID);
        $user = new User();
        ReflectionUtil::setId($user, self::USER_ID);

        $this->tokenAccessor
            ->expects(self::exactly(2))
            ->method('getUser')
            ->willReturn($user);
        // A composite identifier stops the flow before the memo key is built.
        $this->tokenAccessor
            ->expects(self::never())
            ->method('getOrganizationId');

        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('isManageableEntity')
            ->willReturn(true);
        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('isNewEntity')
            ->willReturn(false);
        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getSingleEntityIdentifier')
            ->with($record, false)
            ->willReturn(null);

        $this->authorizationChecker
            ->expects(self::exactly(2))
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $record)
            ->willReturn(true);

        $this->entityAccessChecker->assertPropertyAccessGranted($record, 'name');
        $this->entityAccessChecker->assertPropertyAccessGranted($record, 'name');
    }

    public function testResetEmptiesMemoizedAccessDecisions(): void
    {
        $record = $this->createRecord(self::RECORD_ID);
        $user = new User();
        ReflectionUtil::setId($user, self::USER_ID);

        $this->tokenAccessor
            ->expects(self::exactly(2))
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor
            ->expects(self::exactly(2))
            ->method('getOrganizationId')
            ->willReturn(self::CURRENT_ORGANIZATION_ID);

        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('isManageableEntity')
            ->with($record)
            ->willReturn(true);
        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('isNewEntity')
            ->with($record)
            ->willReturn(false);
        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getSingleEntityIdentifier')
            ->with($record, false)
            ->willReturn(self::RECORD_ID);
        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getEntityClass')
            ->with($record)
            ->willReturn(Organization::class);

        $this->authorizationChecker
            ->expects(self::exactly(2))
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $record)
            ->willReturn(true);

        $this->entityAccessChecker->assertPropertyAccessGranted($record, 'name');

        $this->entityAccessChecker->reset();

        $this->entityAccessChecker->assertPropertyAccessGranted($record, 'name');
    }

    private function createRecord(int $id): Organization
    {
        $organization = new Organization();
        ReflectionUtil::setId($organization, $id);

        return $organization;
    }
}
