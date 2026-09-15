<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Artifacts;

use Behat\Mink\Mink;
use Behat\Mink\Session;
use Oro\Bundle\TestFrameworkBundle\Behat\Artifacts\ArtifactsHandlerInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Artifacts\ScreenshotGenerator;
use PHPUnit\Framework\TestCase;
use WebDriver\Exception\UnexpectedAlertOpen;

class ScreenshotGeneratorTest extends TestCase
{
    public function testTakeSavesScreenshotWithEveryHandler(): void
    {
        $handler = $this->createMock(ArtifactsHandlerInterface::class);
        $handler->expects($this->once())
            ->method('save')
            ->with('screenshot-content')
            ->willReturn('http://example.com/screenshot.png');

        $generator = new ScreenshotGenerator($this->getMink('screenshot-content'), [$handler]);

        $this->assertSame(['http://example.com/screenshot.png'], $generator->take());
    }

    public function testTakeReportsUnavailableScreenshotInsteadOfFailing(): void
    {
        $handler = $this->createMock(ArtifactsHandlerInterface::class);
        $handler->expects($this->never())
            ->method('save');

        $mink = $this->createMock(Mink::class);
        $session = $this->createMock(Session::class);
        $mink->expects($this->any())
            ->method('getSession')
            ->willReturn($session);
        $session->expects($this->once())
            ->method('getScreenshot')
            ->willThrowException(new UnexpectedAlertOpen('unexpected alert open'));

        $generator = new ScreenshotGenerator($mink, [$handler]);

        $this->assertSame(
            ['Screenshot is not available: unexpected alert open'],
            $generator->take()
        );
    }

    private function getMink(string $screenshot): Mink
    {
        $mink = $this->createMock(Mink::class);
        $session = $this->createMock(Session::class);
        $mink->expects($this->any())
            ->method('getSession')
            ->willReturn($session);
        $session->expects($this->any())
            ->method('getScreenshot')
            ->willReturn($screenshot);

        return $mink;
    }
}
