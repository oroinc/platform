<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ParentEntityObjectSecurityCheck;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ParentEntityObjectSecurityCheckTest extends GetSubresourceProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var ParentEntityObjectSecurityCheck */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->processor = new ParentEntityObjectSecurityCheck(
            $this->authorizationChecker,
            'VIEW'
        );
    }

    public function testProcessWhenNoParentEntity()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessGranted()
    {
        $parentEntity = new Product();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($parentEntity))
            ->willReturn(true);

        $this->context->setParentClassName(get_class($parentEntity));
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWhenAccessDenied()
    {
        $parentEntity = new Product();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($parentEntity))
            ->willReturn(false);

        $this->context->setParentClassName(get_class($parentEntity));
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
    }
}
