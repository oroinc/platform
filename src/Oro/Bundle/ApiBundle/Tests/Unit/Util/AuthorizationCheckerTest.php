<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Contact as TestEntity;
use Oro\Bundle\ApiBundle\Util\AuthorizationChecker;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AuthorizationCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    private $innerAuthorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclGroupProviderInterface */
    private $aclGroupProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var AuthorizationChecker */
    private $authorizationChecker;

    protected function setUp()
    {
        parent::setUp();

        $this->innerAuthorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->aclGroupProvider = $this->createMock(AclGroupProviderInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->authorizationChecker = new AuthorizationChecker(
            $this->innerAuthorizationChecker,
            $this->aclGroupProvider,
            $this->doctrineHelper
        );
    }

    public function testIsGrantedForUnknownStringSubject()
    {
        $attributes = 'PERMISSION';
        $subject = 'test';

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');
        $this->aclGroupProvider->expects(self::never())
            ->method('getGroup');

        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attributes, $subject)
            ->willReturn(false);

        self::assertFalse(
            $this->authorizationChecker->isGranted($attributes, $subject)
        );
    }

    public function testIsGrantedForNotManageableEntityClass()
    {
        $attributes = 'PERMISSION';
        $subject = TestEntity::class;

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($subject)
            ->willReturn(false);
        $this->aclGroupProvider->expects(self::never())
            ->method('getGroup');

        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attributes, $subject)
            ->willReturn(true);

        self::assertTrue(
            $this->authorizationChecker->isGranted($attributes, $subject)
        );
    }

    public function testIsGrantedForManageableEntityClassAndWithoutAclGroup()
    {
        $attributes = 'PERMISSION';
        $subject = TestEntity::class;

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($subject)
            ->willReturn(true);
        $this->aclGroupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn('');

        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attributes, new ObjectIdentity('entity', $subject))
            ->willReturn(true);

        self::assertTrue(
            $this->authorizationChecker->isGranted($attributes, $subject)
        );
    }

    public function testIsGrantedForManageableEntityClassAndAclGroup()
    {
        $attributes = 'PERMISSION';
        $subject = TestEntity::class;
        $aclGroup = 'group';

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($subject)
            ->willReturn(true);
        $this->aclGroupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn($aclGroup);

        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attributes, new ObjectIdentity('entity', $aclGroup . '@' . $subject))
            ->willReturn(true);

        self::assertTrue(
            $this->authorizationChecker->isGranted($attributes, $subject)
        );
    }

    public function testIsGrantedForObjectIdentity()
    {
        $attributes = 'PERMISSION';
        $subject = $this->createMock(ObjectIdentityInterface::class);

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');
        $this->aclGroupProvider->expects(self::never())
            ->method('getGroup');

        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attributes, self::identicalTo($subject))
            ->willReturn(true);

        self::assertTrue(
            $this->authorizationChecker->isGranted($attributes, $subject)
        );
    }

    public function testIsGrantedForNotManageableEntity()
    {
        $attributes = 'PERMISSION';
        $subject = new TestEntity();

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(get_class($subject))
            ->willReturn(false);
        $this->aclGroupProvider->expects(self::never())
            ->method('getGroup');

        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attributes, self::identicalTo($subject))
            ->willReturn(true);

        self::assertTrue(
            $this->authorizationChecker->isGranted($attributes, $subject)
        );
    }

    public function testIsGrantedForManageableEntityAndWithoutAclGroup()
    {
        $attributes = 'PERMISSION';
        $subject = new TestEntity();

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(get_class($subject))
            ->willReturn(true);
        $this->aclGroupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn(false);

        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attributes, self::identicalTo($subject))
            ->willReturn(true);

        self::assertTrue(
            $this->authorizationChecker->isGranted($attributes, $subject)
        );
    }

    public function testIsGrantedForManageableEntityAndAclGroup()
    {
        $attributes = 'PERMISSION';
        $subject = new TestEntity();
        $aclGroup = 'group';

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(get_class($subject))
            ->willReturn(true);
        $this->aclGroupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn($aclGroup);

        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(
                $attributes,
                new DomainObjectWrapper(
                    $subject,
                    new ObjectIdentity('entity', $aclGroup . '@' . get_class($subject))
                )
            )
            ->willReturn(true);

        self::assertTrue(
            $this->authorizationChecker->isGranted($attributes, $subject)
        );
    }
}
