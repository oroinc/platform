<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit;

use Oro\Bundle\InstallerBundle\ChainPlatformUpdateChecker;
use Oro\Bundle\InstallerBundle\PlatformUpdateCheckerInterface;

class ChainPlatformUpdateCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|PlatformUpdateCheckerInterface */
    private $checker1;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PlatformUpdateCheckerInterface */
    private $checker2;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PlatformUpdateCheckerInterface */
    private $checker3;

    /** @var ChainPlatformUpdateChecker */
    private $chainChecker;

    protected function setUp(): void
    {
        $this->checker1 = $this->createMock(PlatformUpdateCheckerInterface::class);
        $this->checker2 = $this->createMock(PlatformUpdateCheckerInterface::class);
        $this->checker3 = $this->createMock(PlatformUpdateCheckerInterface::class);

        $this->chainChecker = new ChainPlatformUpdateChecker([$this->checker1, $this->checker2, $this->checker3]);
    }

    public function testWhenAllCheckersAllowUpdate()
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

    public function testWhenThereAreSeveralCheckersThatDenyUpdate()
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
