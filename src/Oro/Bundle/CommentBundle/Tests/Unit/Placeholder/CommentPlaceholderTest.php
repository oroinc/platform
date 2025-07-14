<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\CommentBundle\Placeholder\CommentPlaceholderFilter;
use Oro\Bundle\CommentBundle\Tests\Unit\Fixtures\TestEntity;
use Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CommentPlaceholderTest extends TestCase
{
    private const TEST_ENTITY_REFERENCE = TestEntity::class;

    private CommentAssociationHelper&MockObject $commentAssociationHelper;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private CommentPlaceholderFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->commentAssociationHelper = $this->createMock(CommentAssociationHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->willReturnCallback(fn ($entity) => !$entity instanceof \stdClass);

        $this->filter = new CommentPlaceholderFilter(
            $this->commentAssociationHelper,
            $doctrineHelper,
            $this->authorizationChecker
        );
    }

    public function testIsApplicableWithNull(): void
    {
        $this->commentAssociationHelper->expects($this->never())
            ->method('isCommentAssociationEnabled');

        $this->assertFalse(
            $this->filter->isApplicable(null)
        );
    }

    public function testIsApplicableWithNonManagedEntity(): void
    {
        $testEntity = new \stdClass();

        $this->commentAssociationHelper->expects($this->never())
            ->method('isCommentAssociationEnabled');

        $this->assertFalse(
            $this->filter->isApplicable($testEntity)
        );
    }

    public function testIsApplicableWithNotObject(): void
    {
        $this->commentAssociationHelper->expects($this->never())
            ->method('isCommentAssociationEnabled');

        $this->assertFalse(
            $this->filter->isApplicable('test')
        );
    }

    public function testIsApplicableWhenPermissionsAreNotGranted(): void
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_comment_view')
            ->willReturn(false);

        $this->commentAssociationHelper->expects($this->never())
            ->method('isCommentAssociationEnabled');

        $this->assertFalse(
            $this->filter->isApplicable(new TestEntity())
        );
    }

    public function testIsApplicableWithCommentAssociationDisabled(): void
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_comment_view')
            ->willReturn(true);

        $this->commentAssociationHelper->expects($this->once())
            ->method('isCommentAssociationEnabled')
            ->with(self::TEST_ENTITY_REFERENCE)
            ->willReturn(false);

        $this->assertFalse($this->filter->isApplicable(new TestEntity()));
    }

    public function testIsApplicableWithCommentAssociationEnabled(): void
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_comment_view')
            ->willReturn(true);

        $this->commentAssociationHelper->expects($this->once())
            ->method('isCommentAssociationEnabled')
            ->with(self::TEST_ENTITY_REFERENCE)
            ->willReturn(true);

        $this->assertTrue($this->filter->isApplicable(new TestEntity()));
    }
}
