<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\CommentBundle\Placeholder\CommentPlaceholderFilter;
use Oro\Bundle\CommentBundle\Tests\Unit\Fixtures\TestEntity;

class CommentPlaceholderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_REFERENCE = 'Oro\Bundle\CommentBundle\Tests\Unit\Fixtures\TestEntity';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $commentAssociationHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var  CommentPlaceholderFilter */
    protected $filter;

    protected function setUp()
    {
        $this->commentAssociationHelper = $this
            ->getMockBuilder('Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper        = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade        = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->willReturnCallback(function ($entity) {
                return !$entity instanceof \stdClass;
            });

        $this->filter = new CommentPlaceholderFilter(
            $this->commentAssociationHelper,
            $this->doctrineHelper,
            $this->securityFacade
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
        $this->securityFacade->expects($this->once())
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
        $this->securityFacade->expects($this->once())
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
        $this->securityFacade->expects($this->once())
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
