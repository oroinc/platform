<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Driver;

use Behat\Mink\Exception\DriverException;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroPlaywrightDriver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Dialog\DialogInterface;
use Playwright\Mink\Driver\PlaywrightDriver;
use Playwright\Page\Page;
use Playwright\Page\PageEventHandlerInterface;
use Playwright\Transport\JsonRpc\JsonRpcTransport;

class OroPlaywrightDriverTest extends TestCase
{
    private const array TIMEOUT_ENVS = ['ORO_PLAYWRIGHT_ACTION_TIMEOUT', 'ORO_PLAYWRIGHT_NAVIGATION_TIMEOUT'];

    /** @var array<string, string|false> */
    private array $originalEnv = [];

    #[\Override]
    protected function setUp(): void
    {
        if (!class_exists(PlaywrightDriver::class)) {
            self::markTestSkipped('playwright-php/playwright-mink is not installed.');
        }

        foreach (self::TIMEOUT_ENVS as $name) {
            $this->originalEnv[$name] = getenv($name);
            putenv($name);
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        foreach ($this->originalEnv as $name => $value) {
            putenv(false === $value ? $name : $name . '=' . $value);
        }
    }

    /**
     * @dataProvider invalidTimeoutDataProvider
     */
    public function testStartRejectsInvalidTimeoutBeforeTheBrowserStarts(string $name, string $value): void
    {
        putenv($name . '=' . $value);

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage(sprintf(
            'The %s environment variable must be a number of milliseconds from 1 to 25000.'
            . ' The current value is "%s".',
            $name,
            $value
        ));

        (new OroPlaywrightDriver())->start();
    }

    public function invalidTimeoutDataProvider(): array
    {
        $rows = [];
        foreach (self::TIMEOUT_ENVS as $name) {
            foreach (['0', '00', '-1', '25001', '30000', 'abc', '1e4', ' 5000'] as $value) {
                $rows[sprintf('%s="%s"', $name, $value)] = ['name' => $name, 'value' => $value];
            }
        }

        return $rows;
    }

    /**
     * testStartAcceptsValidActionTimeout() depends on this order.
     */
    public function testStartChecksActionTimeoutFirst(): void
    {
        putenv('ORO_PLAYWRIGHT_ACTION_TIMEOUT=0');
        putenv('ORO_PLAYWRIGHT_NAVIGATION_TIMEOUT=0');

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('The ORO_PLAYWRIGHT_ACTION_TIMEOUT environment variable');

        (new OroPlaywrightDriver())->start();
    }

    /**
     * The driver reads the action timeout first, see testStartChecksActionTimeoutFirst().
     * An invalid navigation timeout then stops start() before the browser starts.
     * Thus the error about the navigation timeout shows that the driver accepted the action timeout.
     *
     * @dataProvider validActionTimeoutDataProvider
     */
    public function testStartAcceptsValidActionTimeout(string $value): void
    {
        putenv('ORO_PLAYWRIGHT_ACTION_TIMEOUT=' . $value);
        putenv('ORO_PLAYWRIGHT_NAVIGATION_TIMEOUT=25001');

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('The ORO_PLAYWRIGHT_NAVIGATION_TIMEOUT environment variable');

        (new OroPlaywrightDriver())->start();
    }

    public function validActionTimeoutDataProvider(): array
    {
        return [
            'empty value' => ['value' => ''],
            'lower limit' => ['value' => '1'],
            'upper limit' => ['value' => '25000'],
        ];
    }

    /**
     * OroPlaywrightDriver::TRANSPORT_TIMEOUT and TRANSPORT_REPLY_MARGIN mirror these vendor values.
     * A vendor release that changes one of them must change the driver too.
     */
    public function testVendorTransportLimitsMatchTheDriver(): void
    {
        self::assertSame(30000, (new PlaywrightConfig())->timeoutMs);
        self::assertSame(
            5000,
            (new \ReflectionClassConstant(JsonRpcTransport::class, 'OPERATION_TIMEOUT_GRACE_MS'))->getValue()
        );
    }

    /**
     * OroPlaywrightDriver reads and writes these private properties of the wrapped driver.
     * A vendor release that renames one of them must change the driver too.
     *
     * @dataProvider vendorDriverPropertyDataProvider
     */
    public function testVendorDriverHasProperty(string $property): void
    {
        self::assertTrue(property_exists(PlaywrightDriver::class, $property));
    }

    public function vendorDriverPropertyDataProvider(): array
    {
        return [
            'page' => ['property' => 'page'],
            'context' => ['property' => 'context'],
            'frameScope' => ['property' => 'frameScope'],
            'lastResponse' => ['property' => 'lastResponse'],
        ];
    }

    /**
     * visit() gives goto its own timeout. The transport must then extend its limit for the command.
     */
    public function testVendorTransportUsesTheTimeoutOfTheCommand(): void
    {
        $transport = (new \ReflectionClass(JsonRpcTransport::class))->newInstanceWithoutConstructor();

        self::assertSame(
            120000,
            (new \ReflectionMethod(JsonRpcTransport::class, 'extractOperationTimeoutMs'))
                ->invoke($transport, ['action' => 'page.goto', 'options' => ['timeout' => 120000]])
        );
    }

    /**
     * The event emitter of a cached page keeps every listener. A second listener would queue each dialog twice.
     */
    public function testDialogListenerIsInstalledOncePerPage(): void
    {
        $eventHandler = $this->createMock(PageEventHandlerInterface::class);
        $eventHandler->expects(self::once())
            ->method('onDialog');
        $driver = $this->createDriverOnPage($this->createPage($eventHandler));

        $installDialogListener = new \ReflectionMethod(OroPlaywrightDriver::class, 'installDialogListener');
        $installDialogListener->invoke($driver);
        $installDialogListener->invoke($driver);
    }

    public function testVisitDismissesTheDialogOfTheCurrentPageAndFails(): void
    {
        $page = $this->createPage();
        $driver = $this->createDriverOnPage($page);
        $dialog = $this->createDialog($page, 'confirm', 'Leave the page?');
        $dialog->expects(self::once())
            ->method('dismiss');
        $this->setPendingDialogs($driver, [$dialog]);

        try {
            $driver->visit('http://localhost/next');
            self::fail('visit() must fail while a dialog of the current page is open.');
        } catch (DriverException $e) {
            self::assertStringContainsString('cannot visit http://localhost/next', $e->getMessage());
            self::assertStringContainsString('confirm dialog', $e->getMessage());
            self::assertStringContainsString('"Leave the page?"', $e->getMessage());
        }

        self::assertSame([], $this->getPendingDialogs($driver));
    }

    /**
     * The test page has no transport, so the navigation itself fails after the dialog check.
     */
    public function testVisitIgnoresTheDialogOfAnotherPageAndPutsTheUrlIntoTheNavigationError(): void
    {
        $driver = $this->createDriverOnPage($this->createPage());
        $dialog = $this->createDialog($this->createPage(), 'alert', 'Another tab');
        $dialog->expects(self::never())
            ->method('dismiss');
        $this->setPendingDialogs($driver, [$dialog]);

        try {
            $driver->visit('http://localhost/next');
            self::fail('A page without a transport cannot navigate.');
        } catch (DriverException $e) {
            self::assertStringStartsWith(
                'Cannot visit http://localhost/next. The navigation request failed: ',
                $e->getMessage()
            );
        }

        self::assertSame([$dialog], $this->getPendingDialogs($driver));
    }

    public function testAcceptAlertAnswersOnlyTheDialogOfTheCurrentPage(): void
    {
        $page = $this->createPage();
        $driver = $this->createDriverOnPage($page);
        $otherDialog = $this->createDialog($this->createPage(), 'alert', 'Another tab');
        $otherDialog->expects(self::never())
            ->method('accept');
        $currentDialog = $this->createDialog($page, 'alert', 'Current tab');
        $currentDialog->expects(self::once())
            ->method('accept');
        $this->setPendingDialogs($driver, [$otherDialog, $currentDialog]);

        self::assertTrue($driver->acceptAlert());
        self::assertSame([$otherDialog], $this->getPendingDialogs($driver));
    }

    /**
     * @dataProvider rendererCrashDataProvider
     */
    public function testIsRendererCrash(string $message, bool $expected): void
    {
        self::assertSame(
            $expected,
            (new \ReflectionMethod(OroPlaywrightDriver::class, 'isRendererCrash'))
                ->invoke(new OroPlaywrightDriver(), new \RuntimeException($message))
        );
    }

    public function rendererCrashDataProvider(): array
    {
        return [
            'protocol call' => ['message' => 'Protocol error (Runtime.evaluate): Target crashed', 'expected' => true],
            'goto' => ['message' => 'page.goto: Page crashed', 'expected' => true],
            'load state wait' => [
                'message' => 'page.waitForLoadState: Navigation failed because page crashed!',
                'expected' => true,
            ],
            'aborted navigation' => [
                'message' => 'page.goto: net::ERR_ABORTED at http://localhost/',
                'expected' => false,
            ],
            'transport timeout' => ['message' => 'JSON-RPC request 12 timed out', 'expected' => false],
        ];
    }

    public function testSetVendorPropertyFailsForAPropertyThatTheWrappedDriverDoesNotHave(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('has no "noSuchProperty" property');

        (new \ReflectionMethod(OroPlaywrightDriver::class, 'setVendorProperty'))
            ->invoke(new OroPlaywrightDriver(), 'noSuchProperty', null);
    }

    private function createPage(?PageEventHandlerInterface $eventHandler = null): Page
    {
        $page = (new \ReflectionClass(Page::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(Page::class, 'eventHandler'))
            ->setValue($page, $eventHandler ?? $this->createMock(PageEventHandlerInterface::class));

        return $page;
    }

    private function createDriverOnPage(Page $page): OroPlaywrightDriver
    {
        $driver = new OroPlaywrightDriver();
        $vendorDriver = (new \ReflectionProperty(OroPlaywrightDriver::class, 'driver'))->getValue($driver);
        (new \ReflectionProperty(PlaywrightDriver::class, 'page'))->setValue($vendorDriver, $page);

        return $driver;
    }

    private function createDialog(Page $page, string $type, string $message): DialogInterface&MockObject
    {
        $dialog = $this->createMock(DialogInterface::class);
        $dialog->expects(self::any())
            ->method('page')
            ->willReturn($page);
        $dialog->expects(self::any())
            ->method('type')
            ->willReturn($type);
        $dialog->expects(self::any())
            ->method('message')
            ->willReturn($message);

        return $dialog;
    }

    private function setPendingDialogs(OroPlaywrightDriver $driver, array $dialogs): void
    {
        (new \ReflectionProperty(OroPlaywrightDriver::class, 'pendingDialogs'))->setValue($driver, $dialogs);
    }

    private function getPendingDialogs(OroPlaywrightDriver $driver): array
    {
        return (new \ReflectionProperty(OroPlaywrightDriver::class, 'pendingDialogs'))->getValue($driver);
    }
}
