<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\AttachmentBundle\Api\Processor\ValidateFileViewAccess;
use Oro\Bundle\AttachmentBundle\Entity\File;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ValidateFileViewAccessTest extends GetProcessorTestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private ValidateFileViewAccess $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->processor = new ValidateFileViewAccess($this->authorizationChecker);
    }

    public function testProcessWhenAccessGranted()
    {
        $fileClass = File::class;
        $fileId = 123;

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity($fileId, $fileClass))
            ->willReturn(true);

        $this->context->setClassName($fileClass);
        $this->context->setId($fileId);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessDenied()
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('No access to the entity.');

        $fileClass = File::class;
        $fileId = 123;

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity($fileId, $fileClass))
            ->willReturn(false);

        $this->context->setClassName($fileClass);
        $this->context->setId($fileId);
        $this->processor->process($this->context);
    }
}
