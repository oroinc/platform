<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\CommentBundle\Placeholder\CommentPlaceholderFilter;
use Oro\Bundle\CommentBundle\Tests\Unit\Fixtures\TestEntity;
use Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CommentPlaceholderTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ENTITY_REFERENCE = 'Oro\Bundle\CommentBundle\Tests\Unit\Fixtures\TestEntity';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $commentAssociationHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var  CommentPlaceholderFilter */
    protected $filter;

    protected function setUp()
    {
        $this->commentAssociationHelper = $this->createMock(CommentAssociationHelper::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->willReturnCallback(function ($entity) {
                return !$entity instanceof \stdClass;
            });

        $this->filter = new CommentPlaceholderFilter(
            $this->commentAssociationHelper,
            $this->doctrineHelper,
            $this->authorizationChecker
        );
    }

    public function testIsApplicableWithNull()
    {
        $this->commentAssociationHelper->expects($this->never())
            ->method('isCommentAssociationEnabled');

        $this->assertFalse(
            $this->filter->isApplicable(null)
        );
    }

    public function testIsApplicableWithNonManagedEntity()
    {
        $testEntity = new \stdClass();

        $this->commentAssociationHelper->expects($this->never())
            ->method('isCommentAssociationEnabled');

        $this->assertFalse(
            $this->filter->isApplicable($testEntity)
        );
    }

    public function testIsApplicableWithNotObject()
    {
        $this->commentAssociationHelper->expects($this->never())
            ->method('isCommentAssociationEnabled');

        $this->assertFalse(
            $this->filter->isApplicable('test')
        );
    }

    public function testIsApplicableWhenPermissionsAreNotGranted()
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

    public function testIsApplicableWithCommentAssociationDisabled()
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_comment_view')
            ->willReturn(true);

        $this->commentAssociationHelper->expects($this->once())
            ->method('isCommentAssociationEnabled')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue(false));

        $this->assertFalse($this->filter->isApplicable(new TestEntity()));
    }

    public function testIsApplicableWithCommentAssociationEnabled()
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_comment_view')
            ->willReturn(true);

        $this->commentAssociationHelper->expects($this->once())
            ->method('isCommentAssociationEnabled')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue(true));

        $this->assertTrue($this->filter->isApplicable(new TestEntity()));
    }
}
