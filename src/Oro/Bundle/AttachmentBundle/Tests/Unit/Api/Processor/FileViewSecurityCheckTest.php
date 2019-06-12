<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\AttachmentBundle\Api\Processor\FileViewSecurityCheck;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Component\ChainProcessor\ContextInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class FileViewSecurityCheckTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var FileViewSecurityCheck */
    private $processor;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->context = $this->createMock(ContextInterface::class);
        $this->processor = new FileViewSecurityCheck($this->authorizationChecker);
    }

    public function testProcessWhenGranted(): void
    {
        $this->context
            ->method('get')
            ->willReturnMap([
                ['class', $fileClass = File::class],
                ['id', $fileId = 1],
            ]);

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity($fileId, $fileClass))
            ->willReturn(true);

        $this->processor->process($this->context);
    }

    public function testProcessWhenNotGranted(): void
    {
        $this->context
            ->method('get')
            ->willReturnMap([
                ['class', $fileClass = File::class],
                ['id', $fileId = 1],
            ]);

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity($fileId, $fileClass))
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->processor->process($this->context);
    }
}
