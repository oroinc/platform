<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ParentEntityObjectSecurityCheck;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;

class ParentEntityObjectSecurityCheckTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $authorizationChecker;

    /** @var ParentEntityObjectSecurityCheck */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->processor = new ParentEntityObjectSecurityCheck(
            $this->doctrineHelper,
            $this->authorizationChecker,
            'VIEW'
        );
    }

    public function testProcessWhenNoParentEntity()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessGrantedForManageableParentEntity()
    {
        $parentEntity = new Product();

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($parentEntity))
            ->willReturn(true);

        $this->context->setParentClassName(get_class($parentEntity));
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWhenAccessDeniedForManageableParentEntity()
    {
        $parentEntity = new Product();

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($parentEntity))
            ->willReturn(false);

        $this->context->setParentClassName(get_class($parentEntity));
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
    }

    public function testAccessShouldBeAlwaysGrantedForNotManageableParentEntity()
    {
        $parentEntity = new \stdClass();

        $this->notManageableClassNames = [get_class($parentEntity)];

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->context->setParentClassName(get_class($parentEntity));
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
    }
}
