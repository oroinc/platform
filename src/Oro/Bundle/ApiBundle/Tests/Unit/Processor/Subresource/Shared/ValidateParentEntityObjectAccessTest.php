<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityObjectAccess;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ValidateParentEntityObjectAccessTest extends ChangeRelationshipProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var ValidateParentEntityObjectAccess */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->processor = new ValidateParentEntityObjectAccess(
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

    public function testProcessWhenAccessDenied()
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('No access by "VIEW" permission to the parent entity.');

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
