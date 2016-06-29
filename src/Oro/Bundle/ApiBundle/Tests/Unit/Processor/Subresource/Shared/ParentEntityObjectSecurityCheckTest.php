<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ParentEntityObjectSecurityCheck;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;

class ParentEntityObjectSecurityCheckTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var ParentEntityObjectSecurityCheck */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new ParentEntityObjectSecurityCheck($this->doctrineHelper, $this->securityFacade, 'VIEW');
    }

    public function testProcessWhenNoParentEntity()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessGrantedForManageableParentEntity()
    {
        $parentEntity = new Product();

        $this->securityFacade->expects($this->once())
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

        $this->securityFacade->expects($this->once())
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

        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        $this->context->setParentClassName(get_class($parentEntity));
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
    }
}
