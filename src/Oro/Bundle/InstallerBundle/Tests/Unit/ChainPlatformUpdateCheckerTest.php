<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit;

use Oro\Bundle\InstallerBundle\ChainPlatformUpdateChecker;
use Oro\Bundle\InstallerBundle\PlatformUpdateCheckerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChainPlatformUpdateCheckerTest extends TestCase
{
    private PlatformUpdateCheckerInterface&MockObject $checker1;
    private PlatformUpdateCheckerInterface&MockObject $checker2;
    private PlatformUpdateCheckerInterface&MockObject $checker3;
    private ChainPlatformUpdateChecker $chainChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->checker1 = $this->createMock(PlatformUpdateCheckerInterface::class);
        $this->checker2 = $this->createMock(PlatformUpdateCheckerInterface::class);
        $this->checker3 = $this->createMock(PlatformUpdateCheckerInterface::class);

        $this->chainChecker = new ChainPlatformUpdateChecker([$this->checker1, $this->checker2, $this->checker3]);
    }

    public function testWhenAllCheckersAllowUpdate(): void
    {
        $this->checker1->expects(self::once())
            ->method('checkReadyToUpdate')
            ->willReturn(null);
        $this->checker2->expects(self::once())
            ->method('checkReadyToUpdate')
            ->willReturn([]);
        $this->checker3->expects(self::once())
            ->method('checkReadyToUpdate')
            ->willReturn(null);

        self::assertNull(
            $this->chainChecker->checkReadyToUpdate()
        );
    }

    public function testWhenThereAreSeveralCheckersThatDenyUpdate(): void
    {
        $this->checker1->expects(self::once())
            ->method('checkReadyToUpdate')
            ->willReturn(null);
        $this->checker2->expects(self::once())
            ->method('checkReadyToUpdate')
            ->willReturn(['message 1']);
        $this->checker3->expects(self::once())
            ->method('checkReadyToUpdate')
            ->willReturn(['message 2', 'message 3']);

        self::assertSame(
            ['message 1', 'message 2', 'message 3'],
            $this->chainChecker->checkReadyToUpdate()
        );
    }
}
