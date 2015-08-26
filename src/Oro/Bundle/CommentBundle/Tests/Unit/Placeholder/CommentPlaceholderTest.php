<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\CommentBundle\Placeholder\CommentPlaceholderFilter;
use Oro\Bundle\CommentBundle\Tests\Unit\Fixtures\TestEntity;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class CommentPlaceholderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_REFERENCE = 'Oro\Bundle\CommentBundle\Tests\Unit\Fixtures\TestEntity';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $commentConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var  CommentPlaceholderFilter */
    protected $filter;

    protected function setUp()
    {
        $this->commentConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider  = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
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
            $this->commentConfigProvider,
            $this->entityConfigProvider,
            $this->doctrineHelper,
            $this->securityFacade
        );
    }

    public function testIsApplicableWithNull()
    {
        $this->commentConfigProvider->expects($this->never())
            ->method('hasConfig');

        $this->assertFalse(
            $this->filter->isApplicable(null)
        );
    }

    public function testIsApplicableWithNonManagedEntity()
    {
        $testEntity = new \stdClass();
        $this->assertFalse(
            $this->filter->isApplicable($testEntity)
        );
    }

    public function testIsApplicableWithNotObject()
    {
        $this->commentConfigProvider->expects($this->never())
            ->method('hasConfig');

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

        $this->assertFalse(
            $this->filter->isApplicable(new TestEntity())
        );
    }

    public function testIsApplicableWithNotConfigurableEntity()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_comment_view')
            ->willReturn(true);

        $this->commentConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue(false));

        $this->assertFalse($this->filter->isApplicable(new TestEntity()));
    }

    public function testIsApplicableWithNotUpdatedSchema()
    {
        $config = new Config(new EntityConfigId('comment', static::TEST_ENTITY_REFERENCE));
        $config->set('enabled', true);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_comment_view')
            ->willReturn(true);

        $this->commentConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue(true));
        $this->commentConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue($config));
        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(Comment::ENTITY_NAME, ExtendHelper::buildAssociationName(static::TEST_ENTITY_REFERENCE))
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->filter->isApplicable(new TestEntity())
        );
    }

    public function testIsApplicable()
    {
        $config = new Config(new EntityConfigId('comment', static::TEST_ENTITY_REFERENCE));
        $config->set('enabled', true);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_comment_view')
            ->willReturn(true);

        $this->commentConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue(true));
        $this->commentConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(static::TEST_ENTITY_REFERENCE)
            ->will($this->returnValue($config));
        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(Comment::ENTITY_NAME, ExtendHelper::buildAssociationName(static::TEST_ENTITY_REFERENCE))
            ->will($this->returnValue(true));

        $this->assertTrue(
            $this->filter->isApplicable(new TestEntity())
        );
    }
}
