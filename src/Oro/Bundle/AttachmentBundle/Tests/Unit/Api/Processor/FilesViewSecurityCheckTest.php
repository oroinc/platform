<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\AttachmentBundle\Api\Processor\FilesViewSecurityCheck;
use Oro\Bundle\AttachmentBundle\Api\Processor\FileViewSecurityCheck;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Component\ChainProcessor\ContextInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FilesViewSecurityCheckTest extends \PHPUnit\Framework\TestCase
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
        $this->processor = new FilesViewSecurityCheck($this->authorizationChecker);
    }

    public function testProcessWhenNoResult(): void
    {
        $this->context
            ->method('get')
            ->with('class')
            ->willReturn($fileClass = File::class);

        $this->context
            ->expects($this->once())
            ->method('getResult')
            ->willReturn(null);

        $this->authorizationChecker
            ->expects($this->never())
            ->method('isGranted');

        $this->processor->process($this->context);
    }

    public function testProcess(): void
    {
        $this->context
            ->method('get')
            ->with('class')
            ->willReturn($fileClass = File::class);

        $this->context
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($result = [['id' => $file1Id = 1], ['id' => $file2Id = 2]]);

        $this->authorizationChecker
            ->method('isGranted')
            ->withConsecutive(
                ['VIEW', new ObjectIdentity($file1Id, $fileClass)],
                ['VIEW', new ObjectIdentity($file2Id, $fileClass)]
            )
            ->willReturnOnConsecutiveCalls(false, true);

        $this->context
            ->expects(self::once())
            ->method('setResult')
            ->with([['id' => $file2Id]]);

        $this->processor->process($this->context);
    }
}
