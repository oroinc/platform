<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub\TestablePlaywrightBootstrapHandler;
use PHPUnit\Framework\TestCase;

class PlaywrightBootstrapHandlerTest extends TestCase
{
    private const string ENV = 'ORO_BEHAT_PLAYWRIGHT';

    private string|false $originalEnv;

    #[\Override]
    protected function setUp(): void
    {
        $this->originalEnv = getenv(self::ENV);
        TestablePlaywrightBootstrapHandler::reset();
    }

    #[\Override]
    protected function tearDown(): void
    {
        putenv(false === $this->originalEnv ? self::ENV : self::ENV . '=' . $this->originalEnv);
    }

    /**
     * @dataProvider disabledFlagDataProvider
     */
    public function testBootstrapDoesNothingWhenFlagIsOff(?string $flag): void
    {
        putenv(null === $flag ? self::ENV : self::ENV . '=' . $flag);

        TestablePlaywrightBootstrapHandler::bootstrap($this->getEvent('/app/vendor', 300));

        self::assertEquals([], TestablePlaywrightBootstrapHandler::$invocations);
    }

    public function disabledFlagDataProvider(): array
    {
        return [
            'not set' => ['flag' => null],
            'empty' => ['flag' => ''],
            'zero' => ['flag' => '0'],
            'false' => ['flag' => 'false'],
            'no' => ['flag' => 'no'],
        ];
    }

    /**
     * @dataProvider enabledFlagDataProvider
     */
    public function testBootstrapRunsScriptForApplicationWhenFlagIsOn(
        string $flag,
        int|string $processTimeout,
        int $expectedTimeout
    ): void {
        putenv(self::ENV . '=' . $flag);

        TestablePlaywrightBootstrapHandler::bootstrap($this->getEvent('/app/vendor', $processTimeout));

        self::assertEquals(
            [
                [
                    'cmd' => ['bash', $this->getScriptPath(), '/app'],
                    'timeout' => $expectedTimeout,
                    'cwd' => '/app',
                ],
            ],
            TestablePlaywrightBootstrapHandler::$invocations
        );
    }

    public function enabledFlagDataProvider(): array
    {
        return [
            'one' => ['flag' => '1', 'processTimeout' => 300, 'expectedTimeout' => 300],
            'true' => ['flag' => 'true', 'processTimeout' => '600', 'expectedTimeout' => 600],
            'yes, negative timeout means no timeout' => [
                'flag' => 'yes',
                'processTimeout' => '-5',
                'expectedTimeout' => 0,
            ],
        ];
    }

    public function testBootstrapStopsInstallationWhenScriptFails(): void
    {
        putenv(self::ENV . '=1');
        TestablePlaywrightBootstrapHandler::$exitCode = 1;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The Playwright bootstrap failed with the exit code 1. ORO_BEHAT_PLAYWRIGHT has a true value'
        );

        TestablePlaywrightBootstrapHandler::bootstrap($this->getEvent('/app/vendor', 300));
    }

    public function testScriptIsShippedWithBundle(): void
    {
        self::assertFileExists($this->getScriptPath());
        self::assertTrue(is_executable($this->getScriptPath()));
    }

    private function getScriptPath(): string
    {
        return \dirname(__DIR__, 3) . '/Resources/bin/playwright-bootstrap';
    }

    private function getEvent(string $vendorDir, int|string $processTimeout): Event
    {
        $config = $this->createMock(Config::class);
        $config->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['vendor-dir', 0, $vendorDir],
                ['process-timeout', 0, $processTimeout],
            ]);

        $composer = $this->createMock(Composer::class);
        $composer->expects(self::any())
            ->method('getConfig')
            ->willReturn($config);

        $event = $this->createMock(Event::class);
        $event->expects(self::any())
            ->method('getComposer')
            ->willReturn($composer);
        $event->expects(self::any())
            ->method('getIO')
            ->willReturn($this->createMock(IOInterface::class));

        return $event;
    }
}
