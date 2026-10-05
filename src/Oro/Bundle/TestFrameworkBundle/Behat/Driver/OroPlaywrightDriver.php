<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Driver;

use Behat\Mink\Driver\CoreDriver;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Session;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\AssertTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\ScreenshotTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\ElementValueInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Session\Mink\WatchModeSessionHolder;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Dialog\DialogInterface;
use Playwright\Exception\TimeoutException;
use Playwright\Frame\FrameLocatorInterface;
use Playwright\Input\Mouse;
use Playwright\Locator\LocatorInterface;
use Playwright\Mink\Driver\PlaywrightDriver;
use Playwright\Page\PageInterface;
use WebDriver\Exception\NoSuchElement;

/**
 * Mink driver based on playwright-php/playwright-mink with the Oro-specific API of OroSelenium2Driver.
 *
 * PlaywrightDriver is final. Thus this class wraps it and forwards the Mink DriverInterface calls to it.
 * The reflection helpers read and write the private state of the wrapped driver for the same reason.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroPlaywrightDriver extends CoreDriver
{
    use AssertTrait;
    use ScreenshotTrait;

    private PlaywrightDriver $driver;

    /** @var array{width: int, height: int} */
    private array $viewport;

    /**
     * A session that emulates a device keeps its viewport when the window changes size.
     * The Selenium mobileEmulation option kept it, but resizeWindow() of Playwright calls setViewportSize().
     * OroMainContext::switchToActorWindowSession() resizes the window on every "I proceed as" step.
     */
    private bool $emulatesDevice = false;

    private ?string $browsersPath = null;

    /** @var DialogInterface[] Dialogs opened by the page and not yet responded to */
    private array $pendingDialogs = [];

    /** @var \WeakMap<PageInterface, true> Pages that have the dialog listener */
    private \WeakMap $dialogListenerPages;

    private bool $tracing = false;

    /**
     * The limit in ms for one JSON-RPC round trip to the Node bridge.
     * The wrapped driver starts Playwright without a configuration, so the limit is the PlaywrightConfig default.
     */
    private const int TRANSPORT_TIMEOUT = 30000;

    /**
     * A Playwright timeout must end this number of ms before the transport limit.
     * Then the Playwright error reaches the driver first.
     * The value equals the grace that the transport adds to a command with its own timeout.
     */
    private const int TRANSPORT_REPLY_MARGIN = 5000;

    private const int DEFAULT_ACTION_TIMEOUT = 10000;

    private const int DEFAULT_NAVIGATION_TIMEOUT = 25000;

    /**
     * The limit in ms from the start of visit() to the commit of the requested document.
     * A slow server can answer after the navigation timeout. WebDriver waited for it until its page load timeout.
     * The goto command carries this timeout, so the transport extends its own limit for the command.
     */
    private const int NAVIGATION_COMMIT_TIMEOUT = 120000;

    /** The limit in ms for the parse of a document whose 'load' event did not occur. */
    private const int DOCUMENT_PARSE_TIMEOUT = 10000;

    /** resolveTimeouts() sets this Playwright action timeout, in ms. */
    private int $actionTimeout = self::DEFAULT_ACTION_TIMEOUT;

    /** resolveTimeouts() sets this Playwright navigation timeout, in ms. */
    private int $navigationTimeout = self::DEFAULT_NAVIGATION_TIMEOUT;

    /** Interval in ms at which a long wait makes sure again that the renderer is alive */
    private const int CRASH_PROBE_INTERVAL = 5000;

    private ?string $crashTraceDir = null;

    private ?string $crashTraceLabel = null;

    private bool $traceScenarioGroupOpen = false;

    private bool $traceStepGroupOpen = false;

    /** @var array{name: string, location: array{file: string, line: int}|null}|null */
    private ?array $pendingTraceScenario = null;

    public function __construct(
        string $browserType = 'chromium',
        bool $headless = true,
        array $launchOptions = [],
        array $viewport = ['width' => 1920, 'height' => 1080],
        array $contextOptions = []
    ) {
        $this->driver = new PlaywrightDriver($browserType, $headless, $launchOptions, $contextOptions);
        $this->dialogListenerPages = new \WeakMap();
        $this->viewport = $viewport;
        $this->emulatesDevice = !empty($contextOptions['isMobile']);
    }

    /**
     * The Playwright driver does not support watch mode.
     * This method exists because the driver factory wiring needs it.
     */
    public function setSessionHolder(WatchModeSessionHolder $watchSessionHolder): void
    {
    }

    /**
     * Sets the directory inside the project that holds the Playwright browser builds.
     * The playwright-bootstrap script of this bundle installs them there.
     * By default, Playwright uses the home directory. On CI, the install and the tests run as different users.
     * A fixed path inside the project works for both.
     */
    public function setBrowsersPath(?string $browsersPath): void
    {
        $this->browsersPath = $browsersPath;
    }

    /**
     * Playwright has no WebDriver session. MinkSessionManager and JsLogSubscriber accept null.
     *
     * @return null
     */
    public function getWebDriverSession()
    {
        return null;
    }

    #[\Override]
    public function setSession(Session $session)
    {
        parent::setSession($session);
        $this->driver->setSession($session);
    }

    #[\Override]
    public function start()
    {
        $this->resolveTimeouts();
        $this->exportBrowsersPath();
        $this->driver->start();
        $this->driver->resizeWindow($this->viewport['width'], $this->viewport['height']);
        $this->pendingDialogs = [];
        $this->dialogListenerPages = new \WeakMap();
        $this->applyDefaultTimeouts();
        $this->installDialogListener();
        $this->installJsErrorCollector();
        $this->startTracing();
    }

    /**
     * The action timeout is lower than the Playwright default of 30 s, so the JS fallbacks in click() and
     * selectOption() start sooner. setDefaultTimeout() also changes the navigation timeout. Thus
     * setDefaultNavigationTimeout() runs after it. The navigation timeout is longer, because the first page load
     * after the Behat isolation has cold caches. The context values apply to a page that this class does not set up,
     * for example a new window.
     */
    private function applyDefaultTimeouts(): void
    {
        try {
            $page = $this->getPage();
            $page->setDefaultTimeout($this->actionTimeout);
            $page->setDefaultNavigationTimeout($this->navigationTimeout);
        } catch (\Throwable) {
        }

        try {
            $context = $this->getContext();
            $context->setDefaultTimeout($this->actionTimeout);
            $context->setDefaultNavigationTimeout($this->navigationTimeout);
        } catch (\Throwable) {
        }
    }

    /**
     * Reads ORO_PLAYWRIGHT_ACTION_TIMEOUT (default 10000) and ORO_PLAYWRIGHT_NAVIGATION_TIMEOUT (default 25000) in ms.
     * Each timeout must end before the transport limit, with a margin for the reply. Thus the cap is 25000 ms.
     * A page default timeout is not a part of the command, so the transport does not extend its limit for it.
     * reload(), back() and forward() use the page default.
     * A value of 0 is an error too, because 0 turns the Playwright timeout off.
     */
    private function resolveTimeouts(): void
    {
        $this->actionTimeout = $this->getTimeoutFromEnv(
            'ORO_PLAYWRIGHT_ACTION_TIMEOUT',
            self::DEFAULT_ACTION_TIMEOUT
        );
        $this->navigationTimeout = $this->getTimeoutFromEnv(
            'ORO_PLAYWRIGHT_NAVIGATION_TIMEOUT',
            self::DEFAULT_NAVIGATION_TIMEOUT
        );
    }

    private function getTimeoutFromEnv(string $name, int $default): int
    {
        $value = getenv($name);
        if (false === $value || '' === $value) {
            return $default;
        }

        $maxTimeout = self::TRANSPORT_TIMEOUT - self::TRANSPORT_REPLY_MARGIN;
        if (!ctype_digit($value) || (int)$value < 1 || (int)$value > $maxTimeout) {
            throw new DriverException(sprintf(
                'The %s environment variable must be a number of milliseconds from 1 to %d.'
                . ' The current value is "%s". A longer timeout ends after the %d ms limit of the'
                . ' Playwright JSON-RPC transport. Then the transport error hides the real error.',
                $name,
                $maxTimeout,
                $value,
                self::TRANSPORT_TIMEOUT
            ));
        }

        return (int)$value;
    }

    /**
     * Gives the browsers directory to the Node bridge that the wrapped driver starts, as PLAYWRIGHT_BROWSERS_PATH.
     * A PLAYWRIGHT_BROWSERS_PATH that is already set wins. Without the directory, Playwright uses its default location.
     * Symfony Process builds the child environment from $_ENV and $_SERVER. Thus this method sets both.
     */
    private function exportBrowsersPath(): void
    {
        if (
            !$this->browsersPath
            || false !== getenv('PLAYWRIGHT_BROWSERS_PATH')
            || !is_dir($this->browsersPath)
        ) {
            return;
        }

        putenv('PLAYWRIGHT_BROWSERS_PATH=' . $this->browsersPath);
        $_ENV['PLAYWRIGHT_BROWSERS_PATH'] = $this->browsersPath;
        $_SERVER['PLAYWRIGHT_BROWSERS_PATH'] = $this->browsersPath;
    }

    /**
     * Starts a Playwright trace recording. ORO_PLAYWRIGHT_TRACE=0 turns it off.
     * The DOM snapshots and the screenshots are off by default, because their buffers stay in the renderer memory for
     * the whole feature. ORO_PLAYWRIGHT_TRACE_SNAPSHOTS=1 and ORO_PLAYWRIGHT_TRACE_SCREENSHOTS=1 turn them on.
     * The trace records the action log, the network and the console errors. A tracing error never stops the run.
     * The network log includes cookies and form posts, and CI publishes the trace zips as artifacts.
     */
    public function startTracing(): void
    {
        if ($this->tracing || !$this->isStarted() || '0' === getenv('ORO_PLAYWRIGHT_TRACE')) {
            return;
        }

        try {
            $this->getContext()->startTracing($this->getPage(), [
                'screenshots' => '1' === getenv('ORO_PLAYWRIGHT_TRACE_SCREENSHOTS'),
                'snapshots' => '1' === getenv('ORO_PLAYWRIGHT_TRACE_SNAPSHOTS'),
            ]);
            $this->tracing = true;
        } catch (\Throwable) {
        }
    }

    /**
     * Keeps the scenario that the next step markers belong to. The first markTraceStep() call opens
     * the scenario group. Thus a session that starts in the middle of a scenario also gets a group.
     *
     * @param array{file: string, line: int}|null $location
     */
    public function setTraceScenario(string $name, ?array $location = null): void
    {
        if ($this->traceScenarioGroupOpen) {
            return;
        }

        $this->pendingTraceScenario = ['name' => $name, 'location' => $location];
    }

    /**
     * Opens a tracing group with the name of the current Behat step and closes the group of the previous step.
     *
     * @param array{file: string, line: int}|null $location Source location shown by the trace viewer
     */
    public function markTraceStep(string $text, ?array $location = null): void
    {
        if (!$this->tracing || !$this->isStarted()) {
            return;
        }

        try {
            if (!$this->traceScenarioGroupOpen && null !== $this->pendingTraceScenario) {
                $this->openTraceGroup(
                    $this->pendingTraceScenario['name'],
                    $this->pendingTraceScenario['location']
                );
                $this->traceScenarioGroupOpen = true;
                $this->pendingTraceScenario = null;
            }

            if ($this->traceStepGroupOpen) {
                $this->traceStepGroupOpen = false;
                $this->sendContextCommand(['action' => 'tracingGroupEnd']);
            }

            $this->openTraceGroup($text, $location);
            $this->traceStepGroupOpen = true;
        } catch (\Throwable) {
        }
    }

    public function endTraceScenario(): void
    {
        $this->pendingTraceScenario = null;

        if (!$this->tracing || !$this->isStarted()) {
            return;
        }

        try {
            $this->closeAllTraceGroups();
        } catch (\Throwable) {
        }
    }

    /**
     * Browser console errors of the current document, which the init script from start() collects.
     * Playwright has no WebDriver log API, which JsLogSubscriber uses for Selenium.
     *
     * @return array<array{type: string, message: string, time: string}>
     */
    public function getCollectedJsErrors(): array
    {
        if (!$this->isStarted()) {
            return [];
        }

        try {
            $errors = $this->evaluateScript('window.__oroBehatJsErrors || []');
        } catch (\Throwable) {
            return [];
        }

        return is_array($errors) ? array_values(array_filter($errors, 'is_array')) : [];
    }

    /**
     * ARIA snapshot of the current page, in the YAML of the "Page snapshot" section of the Playwright HTML report.
     */
    public function getAriaSnapshot(): ?string
    {
        if (!$this->isStarted()) {
            return null;
        }

        try {
            $response = $this->sendPageCommand(['action' => 'page.ariaSnapshot']);
        } catch (\Throwable) {
            return null;
        }

        $value = $response['value'] ?? null;

        return is_string($value) && '' !== trim($value) ? $value : null;
    }

    /**
     * Sets where to write the trace of a browser that the driver is about to discard.
     */
    public function setCrashTraceTarget(?string $traceDir, ?string $label): void
    {
        $this->crashTraceDir = $traceDir;
        $this->crashTraceLabel = $label;
    }

    /**
     * Null in place of the path discards the recording.
     *
     * @return bool TRUE when a trace file was saved
     */
    public function saveTrace(?string $path): bool
    {
        if (!$this->tracing || !$this->isStarted()) {
            return false;
        }

        $this->tracing = false;

        try {
            $this->closeAllTraceGroups();

            if (null === $path) {
                // stopTracing() of the API needs a path. The raw command without a path writes no archive.
                $this->sendContextCommand(['action' => 'context.stopTracing']);

                return false;
            }

            $this->getContext()->stopTracing($this->getPage(), $path);
        } catch (\Throwable) {
            return false;
        }

        return file_exists($path);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function setValue(string $xpath, $value)
    {
        if ($value instanceof ElementValueInterface) {
            $value->set($xpath, $this);

            return;
        }

        $elementName = strtolower($this->getTagName($xpath));

        if ('input' === $elementName) {
            $type = strtolower((string)$this->getAttribute($xpath, 'type'));
            if (in_array($type, ['text', 'range'], true)) {
                $this->setValueByJsWithEvents($xpath, (string)$value);

                return;
            }
            // Selenium2Driver reads a boolean-like value on a checkbox as check() or uncheck().
            // The wrapped driver calls fill() with such a string and stalls on the hidden inputs behind Oro widgets.
            // Only a boolean takes the checked-state path for a radio.
            // A radio string keeps the Mink select-by-value behavior.
            if ('checkbox' === $type && null !== ($bool = $this->toBoolean($value))) {
                $this->setCheckedState($xpath, $type, $bool);

                return;
            }
            if ('radio' === $type && is_bool($value)) {
                $this->setCheckedState($xpath, $type, $value);

                return;
            }
            if ('number' === $type && is_string($value) && !is_numeric($value)) {
                // Playwright fill() refuses a non-numeric value, which negative scenarios type into a number field.
                // WebDriver typed the keys and let the browser filter them.
                $this->typeIntoInput($xpath, $value);

                return;
            }
        } elseif ('textarea' === $elementName && 'true' === $this->getAttribute($xpath, 'aria-hidden')) {
            if (!$this->fillTinyMce($xpath, (string)$value)) {
                $this->setValueByJsWithEvents($xpath, (string)$value);
            }

            return;
        } elseif ('select' === $elementName && is_string($value)) {
            // route through selectOption() to reuse its hidden-select JS fallback
            $this->selectOption($xpath, $value);

            return;
        }

        $this->driver->setValue($xpath, $value);
    }

    #[\Override]
    public function getValue(string $xpath)
    {
        $elementName = strtolower($this->getTagName($xpath));
        $type = $this->getAttribute($xpath, 'type');
        $elementType = $type ? strtolower($type) : null;

        if ('input' === $elementName && 'checkbox' !== $elementType && 'radio' !== $elementType) {
            return $this->executeJsOnXpath($xpath, 'return ({{ELEMENT}}).value;');
        }

        try {
            return $this->driver->getValue($xpath);
        } catch (DriverException $e) {
            if (!str_contains($e->getMessage(), 'Node is not an')) {
                throw $e;
            }

            // Some steps point at a non-input node, for example the "use default" rows of the configuration.
            // WebDriver returned the value attribute of such a node instead of an error.
            return $this->executeJsOnXpath(
                $xpath,
                'return ({{ELEMENT}}).value !== undefined ? ({{ELEMENT}}).value : ({{ELEMENT}}).getAttribute("value");'
            );
        }
    }

    /**
     * @param string $xpath
     * @param string $value
     * @param bool $clearField
     */
    public function typeIntoInput($xpath, $value, $clearField = true)
    {
        $locator = $this->locator($xpath);
        $elementName = strtolower($this->getTagName($xpath));

        if ($clearField && in_array($elementName, ['input', 'textarea'], true)) {
            $locator->fill('');
        } else {
            $this->moveCaretToEnd($xpath);
        }

        foreach ($this->splitWebDriverKeys((string)$value) as [$key, $chunk]) {
            if (null !== $key) {
                $locator->press($key);
            } elseif ('' !== $chunk) {
                $locator->type($chunk);
            }
        }
    }

    /**
     * Splits out the WebDriver key code points, for example `Key::ESCAPE` (U+E00C).
     * Oro puts such values into typeIntoInput(), and Playwright type() types them as characters.
     * Thus typeIntoInput() presses them as keys.
     *
     * @return list<array{0: ?string, 1: string}> key name (pressed) or null with a text chunk (typed)
     */
    private function splitWebDriverKeys(string $value): array
    {
        static $keys = [
            "\u{E003}" => 'Backspace',
            "\u{E004}" => 'Tab',
            "\u{E006}" => 'Enter',
            "\u{E007}" => 'Enter',
            "\u{E00C}" => 'Escape',
            "\u{E00D}" => ' ',
            "\u{E00E}" => 'PageUp',
            "\u{E00F}" => 'PageDown',
            "\u{E010}" => 'End',
            "\u{E011}" => 'Home',
            "\u{E012}" => 'ArrowLeft',
            "\u{E013}" => 'ArrowUp',
            "\u{E014}" => 'ArrowRight',
            "\u{E015}" => 'ArrowDown',
            "\u{E017}" => 'Delete',
        ];

        $chunks = [];
        $text = '';

        foreach (preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            if (isset($keys[$char])) {
                if ('' !== $text) {
                    $chunks[] = [null, $text];
                    $text = '';
                }
                $chunks[] = [$keys[$char], ''];

                continue;
            }

            $text .= $char;
        }

        if ('' !== $text || !$chunks) {
            $chunks[] = [null, $text];
        }

        return $chunks;
    }

    /**
     * Moves the caret to the end, as the Element Send Keys command of WebDriver did. Playwright type() only focuses.
     * Chromium then puts the caret at the start of a contenteditable, for example a CodeMirror expression editor.
     * CodeMirror reads the caret from the DOM selection, so a plain Range is enough.
     */
    private function moveCaretToEnd(string $xpath): void
    {
        try {
            $this->evaluateOnXpathWithoutWaiting(
                $xpath,
                'if (typeof ({{ELEMENT}}).setSelectionRange === "function") {
                    // A selection that is already in place comes from the step, which made
                    // it to overwrite the text. Leave it alone. WebDriver also typed over
                    // such a selection instead of adding text after it.
                    if ({{ELEMENT}}.selectionStart !== {{ELEMENT}}.selectionEnd) {
                        ({{ELEMENT}}).focus();
                        return true;
                    }
                    ({{ELEMENT}}).focus();
                    // gives an error on input types without selection support, for example
                    // number and email
                    try {
                        const length = String(({{ELEMENT}}).value ?? "").length;
                        ({{ELEMENT}}).setSelectionRange(length, length);
                    } catch (e) {}
                    return true;
                }
                // The same applies to a contenteditable element, where the caret counts as
                // much as a selection. The "In" button of the rule editor leaves the caret
                // between the brackets that it inserts. WebDriver moved the caret only as
                // part of the focus, which does nothing on an element that already has the
                // focus. This check runs before focus(), because focus() drops the range.
                const active = document.activeElement;
                const focused = active === {{ELEMENT}} || {{ELEMENT}}.contains(active);
                const existing = window.getSelection();
                const inside = existing && existing.rangeCount > 0
                    && {{ELEMENT}}.contains(existing.anchorNode);
                if (inside && (focused || !existing.isCollapsed)) {
                    return true;
                }
                ({{ELEMENT}}).focus();
                const range = document.createRange();
                range.selectNodeContents({{ELEMENT}});
                range.collapse(false);
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                return true'
            );
        } catch (\Throwable) {
            // the element is detached or cannot take focus, so the caret stays where the browser puts it
        }
    }

    /**
     * @param string $xpath
     * @param string $value
     */
    public function setInnerHtmlForElement($xpath, $value): void
    {
        if ('div' === strtolower($this->getTagName($xpath))) {
            $this->locator($xpath)->evaluate('(el, value) => { el.innerHTML = value; }', $value);
        }
    }

    /**
     * @param int $time Time must be in milliseconds
     * @return bool
     */
    public function waitPageToLoad($time = 60000)
    {
        $jsCheck = <<<JS
        (function () {
            if (document['readyState'] !== 'complete') {
                return false;
            }

            if (document.title === 'Loading...') {
                return false;
            }

            if (document.body.classList.contains('loading') && !document.body.classList.contains('modal-open')) {
                // a confirmation dialog can cover the loading mask
                return false;
            }

            const ladings = ':not(.map-visual-frame):not(.modal-open)>.loader-mask.shown, .lazy-loading';
            if (document.querySelector(ladings) !== null) {
                // a confirmation dialog can cover the loading mask
                return false;
            }

            // loadModules must be available at this point.
            // A simple page has no loadModules, for example the login page, the
            // "forgot password" page and the embedded forms.
            // The checks that follow are valid only for a page that loads loadModules.
            if (typeof loadModules === 'undefined') {
                // a page without app.js never defines loadModules
                if (document.querySelector('script[src*="/app.js"]') === null) {
                    return true;
                }

                // The page declares app.js, but loadModules is not defined yet. Playwright gets
                // to this point earlier than the WebDriver round trips did. Thus the wait gives a
                // limited grace period and does not stay open. The health check page loads app.js
                // and never defines loadModules. After the grace period this matches the Selenium
                // driver, which always returned true here.
                window.__oroLoadModulesWaitStart = window.__oroLoadModulesWaitStart || Date.now();

                return (Date.now() - window.__oroLoadModulesWaitStart) > 5000;
            }

            if ((document.querySelector('script[src*="/app.js"]') !== null
                && (typeof(jQuery) === 'undefined' || jQuery == null))
                || (typeof(jQuery) !== 'undefined' && jQuery.active)
            ) {
                return false;
            }

            return true;
        })();
JS;

        $result = $this->wait($time, $jsCheck);

        if (!$result) {
            self::fail(sprintf('Wait for page init more than %d seconds', $time / 1000));
        }

        return $result;
    }

    /**
     * @param int $time Time must be in milliseconds
     * @return bool
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function waitForAjax($time = 120000)
    {
        $jsAppActiveCheck = <<<JS
        (function () {
            if (document['readyState'] !== 'complete') {
                console.info('Waiting for readyState to be complete');
                return false;
            }

            if (document.title === 'Loading...') {
                console.info('Waiting for "Loading..." title to be removed');
                return false;
            }

            if (document.body.classList.contains('loading') && !document.body.classList.contains('modal-open')) {
                // a confirmation dialog can cover the loading mask
                console.info('Waiting for loading class to be removed from body');
                return false;
            }

            if (document.body.classList.contains('img-loading')) {
                console.info('Waiting for img-loading class to be removed from body');
                return false;
            }

            const ladings = ':not(.map-visual-frame):not(.modal-open)>.loader-mask.shown, .lazy-loading';
            if (document.querySelector(ladings) !== null) {
                // a confirmation dialog can cover the loading mask
                return false;
            }

            // look for the loading bar components
            if (document.querySelector('.loading-bar.show') !== null) {
                console.info('Waiting for .loading-bar to hide');
                return false;
            }

            // look for a loading mask view that is shown
            if (document.querySelector('.loader-mask.shown') !== null) {
                console.info('Waiting for .loader-mask to hide');
                return false;
            }

            // loadModules must be available at this point.
            // A simple page has no loadModules, for example the login page, the
            // "forgot password" page and the embedded forms.
            // The checks that follow are valid only for a page that loads loadModules.
            if (typeof loadModules === 'undefined') {
                // a page without app.js never defines loadModules
                if (document.querySelector('script[src*="/app.js"]') === null) {
                    return true;
                }

                // The page declares app.js, but loadModules is not defined yet. Playwright gets
                // to this point earlier than the WebDriver round trips did. Thus the wait gives a
                // limited grace period and does not stay open. The health check page loads app.js
                // and never defines loadModules. After the grace period this matches the Selenium
                // driver, which always returned true here.
                window.__oroLoadModulesWaitStart = window.__oroLoadModulesWaitStart || Date.now();

                return (Date.now() - window.__oroLoadModulesWaitStart) > 5000;
            }

            try {
                if ((document.querySelector('script[src*="/app.js"]') !== null
                    && (typeof(jQuery) === 'undefined' || jQuery == null))
                    || (typeof(jQuery) !== 'undefined' && jQuery.active)
                ) {
                    console.info('Waiting for app.js to load');
                    return false;
                }

                if (!window.mediatorCachedForSelenium) {
                    loadModules(['oroui/js/mediator'], function(mediator) {
                        window.mediatorCachedForSelenium = mediator;
                    });
                    console.info('Waiting for mediatorCachedForSelenium to load');
                    return false;
                }

                const isInAction = window.mediatorCachedForSelenium.execute('isInAction');
                if (isInAction !== false) {
                    console.info('Waiting for isInAction flag to turn off');
                    return false;
                }

                try {
                    const isRequestPending = window.mediatorCachedForSelenium.execute('isRequestPending');
                    if (isRequestPending === true) {
                        console.info('Waiting for isRequestPending flag to turn off');
                        return false;
                    }
                } catch (e) {
                    // the handler is not available, so proceed
                }
            } catch (e) {
                console.info('Waiting for exception to resolve: ' + e.message);
                return false;
            }

            return true;
        })();
JS;

        $deadline = microtime(true) + $time / 1000;
        $result = $this->wait($time, $jsAppActiveCheck);

        // A debounced request starts about 100 ms after the UI event, for example a typeahead request.
        // A Playwright poll can read the application as idle in that window. The slower WebDriver round trips did not.
        // Thus the wait reads the idle state again one debounce window later, and it continues if a request started.
        while ($result && !$this->hasCurrentPageDialog()) {
            usleep(150000);
            if ($this->wait(100, $jsAppActiveCheck)) {
                break;
            }

            $remaining = (int)ceil(($deadline - microtime(true)) * 1000);
            if ($remaining <= 0) {
                $result = false;
                break;
            }

            $result = $this->wait($remaining, $jsAppActiveCheck);
        }

        if (!$result) {
            $this->takeScreenshot();
            self::fail(sprintf('Wait for ajax more than %d seconds', $time / 1000));
        }

        return $result;
    }

    /**
     * @param string $xpath
     * @param string $script Script that may reference the element as {{ELEMENT}}
     * @param bool $sync Kept for signature compatibility. Playwright evaluation is always synchronous
     * @return mixed
     */
    public function executeJsOnXpath($xpath, $script, $sync = true)
    {
        $script = rtrim(trim(str_replace('{{ELEMENT}}', 'el', $script)), ';');

        return $this->locator($xpath)->evaluate(sprintf('(el) => { %s; }', $script));
    }

    /**
     * Runs a snippet against the element without the locator wait, so a missing element fails at once, as in WebDriver.
     * executeJsOnXpath() waits for the selector until the action timeout. A check for the element before it only makes
     * the window smaller. The evaluateScript() method of the wrapped driver keeps the current frame scope.
     * Thus a read inside an iframe, for example in the WYSIWYG editor, uses the right document.
     */
    private function evaluateOnXpathWithoutWaiting(string $xpath, string $script): mixed
    {
        $body = str_replace('{{ELEMENT}}', 'el', rtrim(trim($script), ';'));

        $result = $this->driver->evaluateScript(sprintf(
            'return (function (xpath) {'
            . ' const el = document.evaluate(xpath, document, null, 9, null).singleNodeValue;'
            . ' if (!el) { return null; }'
            . ' %s'
            . ' })(%s)',
            $body,
            json_encode($xpath)
        ));

        if (null === $result) {
            throw $this->elementNotFound($xpath);
        }

        return $result;
    }

    #[\Override]
    public function keyDown(string $xpath, $char, ?string $modifier = null)
    {
        $charToKeyMap = [
            8 => 'Backspace',
            9 => 'Tab',
            13 => 'Enter',
            27 => 'Escape',
            32 => ' ', // Space
            33 => 'PageUp',
            34 => 'PageDown',
            35 => 'End',
            36 => 'Home',
            37 => 'ArrowLeft',
            38 => 'ArrowUp',
            39 => 'ArrowRight',
            40 => 'ArrowDown',
            45 => 'Insert',
            46 => 'Delete',
            90 => 'Z',
            89 => 'Y'
        ];

        $key = is_int($char) ? chr($char) : (string)$char;
        if (is_int($char) && array_key_exists($char, $charToKeyMap)) {
            $key = $charToKeyMap[$char];
        }
        $keyCode = is_int($char) ? $char : (1 === strlen($key) ? ord($key) : 0);

        $options = [
            'key' => $key,
            'keyCode' => $keyCode,
            'which' => $keyCode,
            'bubbles' => true,
            'altKey' => 'alt' === $modifier,
            'ctrlKey' => 'ctrl' === $modifier,
            'shiftKey' => 'shift' === $modifier,
            'metaKey' => 'meta' === $modifier,
        ];

        $this->locator($xpath)->evaluate(
            '(el, options) => {
                const ev = new KeyboardEvent("keydown", options);
                Object.defineProperty(ev, "keyCode", { value: options.keyCode });
                Object.defineProperty(ev, "which", { value: options.which });
                el.dispatchEvent(ev);
            }',
            $options
        );
    }

    public function switchToIFrameByElement(NodeElement $element)
    {
        $id = $element->getAttribute('id');

        if ($id === null) {
            $elementXpath = $element->getXpath();
            $id = sprintf('iframe-%s', md5($elementXpath));

            $function = <<<JS
(function(){
    var iframeElement = document.evaluate(
        "{$elementXpath}",
        document,
        null,
        XPathResult.FIRST_ORDERED_NODE_TYPE,
        null
    ).singleNodeValue;
    iframeElement.id = "{$id}";
})()
JS;

            $this->executeScript($function);
        }

        $this->switchToIFrame($id);
        $this->waitForFrameDocument();
    }

    /**
     * Waits until the frame leaves the initial about:blank document, as the "Switch To Frame" command of WebDriver did.
     * playwright-mink changes the scope at once. A step can then read the blank document of a frame that still loads,
     * for example the email template preview. Only the switch by element waits. The childElementCount check accepts
     * an about:blank frame that JS fills, for example the GrapesJS canvas.
     * A frame that stays blank for a valid reason costs the action timeout one time.
     */
    private function waitForFrameDocument(): void
    {
        $deadline = microtime(true) + $this->actionTimeout / 1000;

        do {
            try {
                $pending = $this->evaluateScript(
                    'location.href === "about:blank"'
                    . ' && (!document.body || document.body.childElementCount === 0)'
                );
            } catch (\Throwable) {
                return;
            }

            if (!$pending) {
                return;
            }

            usleep(100000);
        } while (microtime(true) < $deadline);
    }

    #[\Override]
    public function isStarted()
    {
        return $this->driver->isStarted();
    }

    #[\Override]
    public function stop()
    {
        $this->tracing = false;
        $this->traceScenarioGroupOpen = false;
        $this->traceStepGroupOpen = false;
        $this->pendingTraceScenario = null;
        $this->pendingDialogs = [];
        $this->driver->stop();
    }

    #[\Override]
    public function reset()
    {
        // An open dialog blocks the navigation to about:blank in the reset of the wrapped driver.
        // Thus the method dismisses each open dialog first.
        // The list can keep a dialog of a tab that the reset closes, so the method clears the list.
        $this->pumpTransportEvents();
        $this->dismissDialogs($this->pendingDialogs);
        $this->pendingDialogs = [];

        $this->driver->reset();
        // A later playwright-php release can create a new context here, without the timeouts of start().
        $this->applyDefaultTimeouts();
    }

    #[\Override]
    public function visit(string $url)
    {
        // An open dialog blocks the navigation. Thus the driver fails at once, as WebDriver did.
        // It reads the pending events first, so it also knows a dialog that opened after the last command.
        // The driver dismisses the dialog before the error, as the WebDriver default "dismiss and notify" does.
        // Thus only the current step fails, and the next scenarios of the feature can navigate.
        $this->pumpTransportEvents();
        $dialogs = $this->getCurrentPageDialogs();
        if ($dialogs) {
            $dialog = $dialogs[0];
            $this->dismissDialogs($dialogs);

            throw new DriverException(sprintf(
                'The driver cannot visit %s, because a JavaScript %s dialog of the current page was open: "%s".'
                . ' The driver dismissed the dialog. Accept or dismiss it in the step that opens it.',
                $url,
                $dialog->type(),
                $dialog->message()
            ));
        }

        try {
            $this->navigateWithRetry($url, microtime(true));
        } catch (DriverException $e) {
            $e = new DriverException(sprintf('Cannot visit %s. %s', $url, $e->getMessage()), 0, $e);
            // All the navigation errors come here, so the browser starts again only one time.
            $this->recoverIfBrowserCrashed($e);

            throw $e;
        }
    }

    /**
     * net::ERR_ABORTED means that the page started its own navigation while goto was in transit, for example a redirect
     * after login or a reload from JS. The get() method of WebDriver never showed this error. Thus the method asks for
     * the URL again. After a second abort, it waits for the load states of the current document. It does not wait for
     * the other navigation to commit. A renderer crash before the commit also gives net::ERR_ABORTED. The retry then
     * fails with "Page crashed", and visit() starts the browser again.
     */
    private function navigateWithRetry(string $url, float $startedAt): void
    {
        try {
            $this->navigate($url, $startedAt);
        } catch (DriverException $e) {
            if (!$this->isNavigationAborted($e)) {
                throw $e;
            }

            usleep(300000);

            try {
                $this->navigate($url, $startedAt);
            } catch (DriverException $retryError) {
                if (!$this->isNavigationAborted($retryError)) {
                    throw $retryError;
                }

                $this->awaitLoadStates();
            }
        }
    }

    private function isNavigationAborted(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return false !== stripos($message, 'page.goto')
            && false !== stripos($message, 'net::ERR_ABORTED');
    }

    /**
     * Opens the URL in two steps. First, goto waits only for the commit, within NAVIGATION_COMMIT_TIMEOUT from
     * the start of visit(). Then awaitLoadStates() waits for the page load. The wrapped driver does both in one goto
     * with the navigation timeout, which a slow server can use up before the commit. A commit after the navigation
     * timeout prints its time, so the CI log shows a slow server. A closed page or context goes to the wrapped driver.
     */
    private function navigate(string $url, float $startedAt): void
    {
        $remaining = (int)(($startedAt + self::NAVIGATION_COMMIT_TIMEOUT / 1000 - microtime(true)) * 1000);
        // the wrapped driver clears its frame scope before each navigation
        $this->setVendorProperty('frameScope', null);

        try {
            $response = $this->getPage()->goto($url, ['waitUntil' => 'commit', 'timeout' => max(1, $remaining)]);
        } catch (\Throwable $e) {
            // The wrapped driver recovers a closed page or a closed context. Its goto waits for the 'load' event
            // with the page default timeout, without the commit budget of this class.
            if (str_contains($e->getMessage(), 'Page not found') || str_contains($e->getMessage(), 'has been closed')) {
                $this->driver->visit($url);

                return;
            }

            throw new DriverException(sprintf('The navigation request failed: %s', $e->getMessage()), 0, $e);
        }

        $committedAfter = microtime(true) - $startedAt;
        if ($committedAfter * 1000 > $this->navigationTimeout) {
            echo sprintf(
                "Playwright: the document of %s committed %.1f s after the start of visit()."
                . " The navigation timeout is %d ms.\n",
                $url,
                $committedAfter,
                $this->navigationTimeout
            );
        }

        $this->awaitLoadStates();

        // The wrapped driver keeps the last response of all requests. Mink reads the status code and the headers
        // of the document from it. Thus the goto response replaces the responses of the page load requests.
        if (null !== $response) {
            $this->setVendorProperty('lastResponse', $response);
        }
    }

    /**
     * Waits for the load of the committed document. Both waits are Playwright events with their own timeouts, not an
     * evaluate, so an open dialog or a busy page cannot extend them. A stalled external resource can block 'load'.
     * WebDriver accepted this state, and the steps test the readiness with waitForAjax() and waitPageToLoad().
     * Thus a 'load' timeout is accepted. The method then waits for the parse of the document, because the fillField()
     * method of Mink looks a field up one time only. A parse timeout is accepted too.
     */
    private function awaitLoadStates(): void
    {
        if (!$this->waitForPageLoadState('load', $this->navigationTimeout)) {
            $this->waitForPageLoadState('domcontentloaded', self::DOCUMENT_PARSE_TIMEOUT);
        }
    }

    private function waitForPageLoadState(string $state, int $timeout): bool
    {
        try {
            $this->getPage()->waitForLoadState($state, ['timeout' => $timeout]);

            return true;
        } catch (TimeoutException $e) {
            // "JSON-RPC request N timed out" is a transport timeout, not the end of a Playwright wait
            if (false === stripos($e->getMessage(), 'exceeded')) {
                throw $this->createLoadStateError($state, $e);
            }

            return false;
        } catch (\Throwable $e) {
            throw $this->createLoadStateError($state, $e);
        }
    }

    private function createLoadStateError(string $state, \Throwable $e): DriverException
    {
        return new DriverException(
            sprintf('The wait for the "%s" load state failed: %s', $state, $e->getMessage()),
            0,
            $e
        );
    }

    private function setVendorProperty(string $name, mixed $value): void
    {
        $driverReflection = new \ReflectionObject($this->driver);
        if (!$driverReflection->hasProperty($name)) {
            throw new DriverException(sprintf(
                'The wrapped %s has no "%s" property. This version of playwright-php/playwright-mink is not supported.',
                PlaywrightDriver::class,
                $name
            ));
        }

        $driverReflection->getProperty($name)->setValue($this->driver, $value);
    }

    /**
     * Asks the browser to release the heap that a feature without isolation collects over its scenarios.
     * Thus the CI container does not stop the renderer in the middle of a feature because of the memory use.
     * It needs the --js-flags=--expose-gc launch flag.
     * It never gives an error, because memory work must not fail a run.
     */
    public function collectGarbage(): void
    {
        try {
            $this->driver->evaluateScript('window.gc && window.gc()');
        } catch (\Throwable) {
            // no guarantee
        }
    }

    #[\Override]
    public function getCurrentUrl()
    {
        return $this->driver->getCurrentUrl();
    }

    #[\Override]
    public function reload()
    {
        $this->driver->reload();
    }

    #[\Override]
    public function forward()
    {
        $this->driver->forward();
    }

    #[\Override]
    public function back()
    {
        $this->driver->back();
    }

    #[\Override]
    public function setBasicAuth($user, string $password)
    {
        $this->driver->setBasicAuth($user, $password);
    }

    #[\Override]
    public function switchToWindow(?string $name = null)
    {
        $name = $this->normalizeWindowName($name);

        if (null !== $name && preg_match('/^\d+$/', $name)) {
            // the Oro contexts use the WebDriver rule that a numeric name is a window index
            $index = (int)$name;
            $name = 0 === $index ? null : ($this->getWindowNames()[$index] ?? $name);
        }

        if (null !== $name) {
            foreach ($this->getContext()->pages() as $page) {
                if ($this->getPageId($page) === $name) {
                    $this->setVendorPage($page);

                    return;
                }
            }
        }

        // fallback, where the wrapped driver matches by window.name, by title or by URL
        $this->driver->switchToWindow($name);
    }

    #[\Override]
    public function switchToIFrame(?string $name = null)
    {
        // WebDriver accepts a number as a frame index, and the Oro WYSIWYG contexts need this. For example,
        // switchToIFrame(0) enters the GrapesJS canvas, whose iframe has no @name and no @id. The wrapped driver
        // reads every value as @name or @id. Thus the index counts the iframes of the top document.
        // A nested frame has no address in either driver.
        if (null !== $name && '' !== $name && ctype_digit($name)) {
            $name = sprintf('xpath=(//iframe)[%d]', (int)$name + 1);
        }

        $this->driver->switchToIFrame($name);
    }

    #[\Override]
    public function setRequestHeader(string $name, string $value)
    {
        $this->driver->setRequestHeader($name, $value);
    }

    #[\Override]
    public function getResponseHeaders()
    {
        return $this->driver->getResponseHeaders();
    }

    #[\Override]
    public function setCookie(string $name, ?string $value = null)
    {
        $host = null === $value ? null : parse_url((string)$this->getPage()->url(), PHP_URL_HOST);

        if (!is_string($host) || '' === $host) {
            $this->driver->setCookie($name, $value);

            return;
        }

        // WebDriver used "/" as the default cookie path, so a cookie applied to the whole site from any page.
        // The wrapped driver uses the path of the current page.
        // rawurlencode matches the rawurldecode in the getCookie() method of the wrapped driver.
        $this->getContext()->addCookies([[
            'name' => $name,
            'value' => rawurlencode($value),
            'domain' => $host,
            'path' => '/',
        ]]);
    }

    #[\Override]
    public function getCookie(string $name)
    {
        return $this->driver->getCookie($name);
    }

    #[\Override]
    public function getStatusCode()
    {
        return $this->driver->getStatusCode();
    }

    #[\Override]
    public function getContent()
    {
        return $this->driver->getContent();
    }

    #[\Override]
    public function getScreenshot()
    {
        return $this->driver->getScreenshot();
    }

    #[\Override]
    public function getWindowNames()
    {
        // WebDriver returned window handles that never changed. The wrapped driver returns page titles, which change
        // on navigation and break the BrowserTabManager aliases. The page IDs of the bridge stay the same.
        $this->pumpTransportEvents();

        $names = [];
        foreach ($this->getContext()->pages() as $page) {
            $names[] = $this->getPageId($page);
        }

        return $names;
    }

    #[\Override]
    public function getWindowName()
    {
        return $this->getPageId($this->getPage());
    }

    #[\Override]
    public function find(string $xpath)
    {
        if ($this->hasCurrentPageDialog()) {
            // An open dialog blocks the page JS, and a query then stalls until the transport timeout.
            // An empty result replaces the UnexpectedAlertOpen error of WebDriver, which the Oro hooks accept.
            // A step that expects the dialog reads it through the alert API, not through the DOM.
            return [];
        }

        return $this->driver->find($xpath);
    }

    #[\Override]
    public function getTagName(string $xpath)
    {
        $this->assertElementAttached($xpath);

        return $this->driver->getTagName($xpath);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    public function getText(string $xpath)
    {
        // This follows Selenium2Driver: a line break becomes one space, and a hidden element gives an empty string.
        // The Oro grid assertions need the double space of "Label: " before a block element.
        // The wrapped driver makes one space out of every run of whitespace and loses it.
        // innerText falls back to textContent for an element that is not rendered. Select2Entities needs an empty
        // text for a hidden item that is already selected. The visibility of an option follows its select element.
        $text = $this->evaluateOnXpathWithoutWaiting(
            $xpath,
            'if ({{ELEMENT}}.tagName === \'OPTION\' || {{ELEMENT}}.tagName === \'OPTGROUP\') {'
            . ' const sel = {{ELEMENT}}.closest(\'select\');'
            . ' if (sel && sel.checkVisibility && !sel.checkVisibility({visibilityProperty: true}))'
            . ' { return \'\'; }'
            // An option is not rendered on its own, so its textContent is normalized as a renderer does.
            . ' return ({{ELEMENT}}.textContent || \'\').replace(/\s+/g, \' \');'
            . ' }'
            . ' if ({{ELEMENT}}.checkVisibility && !{{ELEMENT}}.checkVisibility({visibilityProperty: true}))'
            . ' { return \'\'; }'
            // WebDriver text() left out content that no user can read, for example an inactive slick slide.
            // One script does it, because one more round trip can miss a flash message that hides itself.
            // The check needs aria-hidden and the clipping together. The clipping alone removes a flash message that
            // moves into its container. aria-hidden alone removes visible decoration, for example the "|" separator
            // of the shopping list widget.
            . ' const text = {{ELEMENT}}.innerText;'
            . ' if (!{{ELEMENT}}.querySelectorAll) { return text; }'
            . ' const hidden = {{ELEMENT}}.querySelectorAll(\'[aria-hidden="true"]\');'
            . ' if (hidden.length === 0) { return text; }'
            . ' const clipped = (node) => {'
            . '   const r = node.getBoundingClientRect();'
            . '   if (r.width === 0 || r.height === 0) { return true; }'
            . '   for (let p = node.parentElement; p; p = p.parentElement) {'
            . '     const st = getComputedStyle(p);'
            . '     if (st.overflow === \'visible\' && st.overflowX === \'visible\''
            . '       && st.overflowY === \'visible\') { continue; }'
            . '     const pr = p.getBoundingClientRect();'
            . '     if (r.right <= pr.left || r.left >= pr.right'
            . '       || r.bottom <= pr.top || r.top >= pr.bottom) { return true; }'
            . '   }'
            . '   return false;'
            . ' };'
            // The script hides the nodes instead of cutting their text, because a visible node can have the same text,
            // for example in the data audit dialog over a form with an aria-hidden textarea.
            . ' const drop = [];'
            . ' for (const el of hidden) {'
            . '   if (el.parentElement && el.parentElement.closest(\'[aria-hidden="true"]\'))'
            . '   { continue; }'
            . '   if (!clipped(el)) { continue; }'
            . '   drop.push(el);'
            . ' }'
            . ' if (drop.length === 0) { return text; }'
            . ' const restore = drop.map((el) => [el, el.style.display]);'
            . ' try {'
            . '   for (const el of drop) { el.style.display = \'none\'; }'
            . '   return {{ELEMENT}}.innerText;'
            . ' } finally {'
            . '   for (const [el, d] of restore) { el.style.display = d; }'
            . ' }'
        );

        // WebDriver text() included the content of a textarea, but innerText gives nothing for a form control.
        // The tracking code block, for example, renders its snippet into a textarea. The value goes to the end,
        // not in place, because assertPageContainsText only looks for the text somewhere in the result.
        $text = $this->appendTextareaValues($xpath, (string)$text);

        // Chromium innerText adds blank lines around block elements, which WebDriver text() never gave.
        // Thus one line break replaces each run of blank lines. A space before a line break is rendered text and stays.
        $text = preg_replace("/(?:\n[ \t]*)+\n/", "\n", (string)$text);

        // A renderer and WebDriver text() drop the whitespace at the start of a line, but innerText keeps it.
        // The whitespace before a newline stays, because the audit grids test for the double space that it gives.
        // "Prices: \nProduct Price" thus becomes "Prices:  Product Price".
        $text = preg_replace("/\n[ \t]+/", "\n", (string)$text);

        // innerText separates table cells with a tab, but WebDriver used a space
        return trim(str_replace(["\r\n", "\r", "\n", "\t", "\u{00A0}"], ' ', (string)$text));
    }

    #[\Override]
    public function getHtml(string $xpath)
    {
        $this->assertElementAttached($xpath);

        return $this->driver->getHtml($xpath);
    }

    #[\Override]
    public function getOuterHtml(string $xpath)
    {
        $this->assertElementAttached($xpath);

        return $this->driver->getOuterHtml($xpath);
    }

    #[\Override]
    public function getAttribute(string $xpath, string $name)
    {
        $this->assertElementAttached($xpath);

        return $this->driver->getAttribute($xpath, $name);
    }

    #[\Override]
    public function check(string $xpath)
    {
        $this->setCheckedState($xpath, 'checkbox', true);
    }

    #[\Override]
    public function uncheck(string $xpath)
    {
        $this->setCheckedState($xpath, 'checkbox', false);
    }

    #[\Override]
    public function isChecked(string $xpath)
    {
        // a detached element is not checked. This read must not wait for the element to come back,
        // see assertElementAttached().
        return $this->isElementAttached($xpath) && $this->driver->isChecked($xpath);
    }

    #[\Override]
    public function selectOption(string $xpath, string $value, bool $multiple = false)
    {
        // WebDriver sent no change event for an option that was already selected, because the selectOptionOnElement()
        // method of Mink clicks only an option that is not selected. Playwright and the JS fallback always send it.
        // A repeated event runs the handlers again. For example, the "Storage type" handler of entity extend clears
        // the "Type" field.
        if ($this->selectionAlreadyApplied($xpath, $value, $multiple)) {
            return;
        }

        // A dependent select can build its options again in the background and drop the selection without a message,
        // for example the Type options of an entity field. WebDriver was slow enough to miss this race.
        // Thus the code checks the selection and tries again while the page settles.
        $lastError = null;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            if ($attempt > 0) {
                usleep(300000);
            }

            try {
                $this->doSelectOption($xpath, $value, $multiple);
                $lastError = null;
            } catch (\Throwable $e) {
                $lastError = $e;
            }

            if ($this->isOptionSelected($xpath, $value)) {
                return;
            }
        }

        if (null !== $lastError) {
            throw $lastError;
        }

        throw new DriverException(sprintf('Option "%s" was not applied to the select', $value));
    }

    private function doSelectOption(string $xpath, string $value, bool $multiple): void
    {
        // Playwright refuses a hidden select and waits for a disabled option to become enabled. WebDriver set both.
        // Oro hides many native selects behind styled widgets. The suite also sets disabled options, for example the
        // enum types that the default storage type disables. Thus JS selects the option and sends the events.
        if (false === $this->isElementVisible($xpath) || $this->matchingOptionIsDisabled($xpath, $value)) {
            $this->selectOptionByJs($xpath, $value, $multiple);

            return;
        }

        try {
            $this->driver->selectOption($xpath, $value, $multiple);
        } catch (DriverException $e) {
            if (!$this->isActionTimeout($e)) {
                throw $e;
            }

            $this->selectOptionByJs($xpath, $value, $multiple);
        }
    }

    private function matchingOptionIsDisabled(string $xpath, string $value): bool
    {
        try {
            return (bool)$this->executeJsOnXpath($xpath, sprintf(
                'const v = %s;
                const options = Array.from(({{ELEMENT}}).options || []);
                const match = options.find((o) => o.value === v)
                    || options.find((o) => (o.textContent || "").trim() === v.trim());
                return !!(match && match.disabled);',
                json_encode($value)
            ));
        } catch (\Throwable) {
            return false;
        }
    }

    private function isOptionSelected(string $xpath, string $value): bool
    {
        try {
            return (bool)$this->executeJsOnXpath($xpath, sprintf(
                'const v = %s;
                return Array.from(({{ELEMENT}}).selectedOptions || [])
                    .some((o) => o.value === v || (o.textContent || "").trim() === v.trim());',
                json_encode($value)
            ));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * True when a selection of $value does not change the selection of the element.
     * A single value on a multi-select must still run, because it clears the other options.
     */
    private function selectionAlreadyApplied(string $xpath, string $value, bool $multiple): bool
    {
        try {
            return (bool)$this->executeJsOnXpath($xpath, sprintf(
                'const v = %s;
                const selected = Array.from(({{ELEMENT}}).selectedOptions || []);
                const hit = selected.some((o) => o.value === v || (o.textContent || "").trim() === v.trim());
                return hit && (%s || !({{ELEMENT}}).multiple || selected.length === 1);',
                json_encode($value),
                $multiple ? 'true' : 'false'
            ));
        } catch (\Throwable) {
            return false;
        }
    }

    #[\Override]
    public function isSelected(string $xpath)
    {
        return $this->isElementAttached($xpath) && $this->driver->isSelected($xpath);
    }

    #[\Override]
    public function click(string $xpath)
    {
        $this->awaitElementForClick($xpath);

        // read before the click, because the click often detaches the element
        $target = $this->readClickTarget($xpath);

        // WebDriver clicked an element that fails the Playwright actionability checks, and the Oro steps need this.
        // A hidden element gets a DOM click at once. A stalled visible element gets it after the action timeout.
        if (false === $this->isElementVisible($xpath)) {
            $this->clickByJs($xpath);
            $this->settlePopover($xpath, $target['closedPopover']);

            return;
        }

        $expectsNewTab = $target['newTab'];
        $pagesBefore = $expectsNewTab ? count($this->getContext()->pages()) : 0;

        try {
            $this->driver->click($xpath);
            $this->awaitNewPage($expectsNewTab, $pagesBefore);
        } catch (DriverException $e) {
            // a dialog that the click opened blocks the page JS, and the action times out.
            // The click itself occurred, which is all that WebDriver gave.
            if ($this->hasCurrentPageDialog()) {
                return;
            }
            if (!$this->isActionTimeout($e)) {
                throw $e;
            }

            try {
                $this->clickByJs($xpath);
                $this->awaitNewPage($expectsNewTab, $pagesBefore);
            } catch (DriverException $jsError) {
                if ($this->hasCurrentPageDialog()) {
                    return;
                }

                throw $jsError;
            }
        }

        $this->settlePopover($xpath, $target['closedPopover']);
    }

    /**
     * Gives an element that is about to appear a short grace period before the click.
     * The first() method of playwright-mink calls count() and fails at once, which turns the Playwright auto-wait off.
     * The grace is ORO_PLAYWRIGHT_CLICK_GRACE ms, default 2000.
     * A click on an element that never appears costs the full grace.
     * A missing element gives NoSuchElement, not the DriverException of playwright-mink, see elementNotFound().
     */
    private function awaitElementForClick(string $xpath): void
    {
        if ($this->isElementAttached($xpath)) {
            return;
        }

        $deadline = microtime(true) + ((int)(getenv('ORO_PLAYWRIGHT_CLICK_GRACE') ?: 2000)) / 1000;

        do {
            usleep(50000);

            if ($this->isElementAttached($xpath)) {
                return;
            }
        } while (microtime(true) < $deadline);

        throw $this->elementNotFound($xpath);
    }

    /**
     * Reads the element state that the click needs, before the click can detach or move the element.
     * Both reads use one round trip, because click() is the most frequent action in the suite.
     *
     * @return array{newTab: bool, closedPopover: bool}
     */
    private function readClickTarget(string $xpath): array
    {
        try {
            $state = $this->evaluateOnXpathWithoutWaiting(
                $xpath,
                'return {
                    newTab: !!({{ELEMENT}}).closest(\'[target="_blank"]\'),
                    closedPopover: ({{ELEMENT}}).matches(\'[data-toggle="popover"]\')
                        && !({{ELEMENT}}).hasAttribute("aria-describedby")
                }'
            );
        } catch (\Throwable) {
            $state = null;
        }

        if (!is_array($state)) {
            return ['newTab' => false, 'closedPopover' => false];
        }

        return [
            'newTab' => (bool)($state['newTab'] ?? false),
            'closedPopover' => (bool)($state['closedPopover'] ?? false),
        ];
    }

    /**
     * Opens a popover again when the label focus after the click closes it, see 'focus.popover-hide' in layout.js.
     * The tooltip icons are inside the <label for="..."> of a field, and the label focuses its control.
     * The method uses the popover API of the widget, because a second click runs the same hide handlers.
     * Each click on a closed trigger waits ORO_PLAYWRIGHT_POPOVER_SETTLE ms (default 250) for that focus.
     */
    private function settlePopover(string $xpath, bool $wasClosedTrigger): void
    {
        if (!$wasClosedTrigger) {
            return;
        }

        usleep(((int)(getenv('ORO_PLAYWRIGHT_POPOVER_SETTLE') ?: 250)) * 1000);

        try {
            $this->evaluateOnXpathWithoutWaiting(
                $xpath,
                'if (({{ELEMENT}}).hasAttribute("aria-describedby")) { return true; }
                const $ = window.jQuery;
                if (!$ || !$.fn.popover || !$({{ELEMENT}}).data("bs.popover")) { return false; }
                $({{ELEMENT}}).popover("show");
                return true'
            );
        } catch (\Throwable) {
            // the click detached the element, or it was never a live popover trigger
        }
    }

    /**
     * chromedriver registered a new tab inside the click command. Playwright sends the context "page" event on its own
     * schedule, which can be seconds later. Without a wait, the next step can switch to an old tab.
     * Only a target="_blank" click or a window.open() script waits, until the page count grows.
     * The limit is ORO_PLAYWRIGHT_POPUP_TIMEOUT ms, default 5000. A link that opens no tab costs the full limit.
     */
    private function awaitNewPage(bool $expectsNewTab, int $pagesBefore): void
    {
        if (!$expectsNewTab) {
            return;
        }

        $deadline = microtime(true) + ((int)(getenv('ORO_PLAYWRIGHT_POPUP_TIMEOUT') ?: 5000)) / 1000;

        do {
            $this->pumpTransportEvents();

            try {
                if (count($this->getContext()->pages()) > $pagesBefore) {
                    return;
                }
            } catch (\Throwable) {
                return;
            }

            usleep(50000);
        } while (microtime(true) < $deadline);
    }

    #[\Override]
    public function doubleClick(string $xpath)
    {
        // The native dblclick of Playwright sends the correct mouse sequence, so Syn is not necessary.
        $this->driver->doubleClick($xpath);
    }

    #[\Override]
    public function rightClick(string $xpath)
    {
        $this->driver->rightClick($xpath);
    }

    #[\Override]
    public function attachFile(string $xpath, string $path)
    {
        // FileField checks a relative fixture path against the working directory of PHP.
        // The Node bridge has a different working directory. Thus a relative path must become absolute.
        $absolutePath = realpath($path);

        $this->driver->attachFile($xpath, false !== $absolutePath ? $absolutePath : $path);
    }

    #[\Override]
    public function isVisible(string $xpath)
    {
        // locator.isVisible() does not wait for the selector, so no attachment check is necessary
        try {
            return $this->driver->isVisible($xpath);
        } catch (DriverException $e) {
            // WebDriver answered isVisible() on a missing element with NoSuchElement, and the callers branch on it,
            // for example Grid::hasMassActionLink(). See elementNotFound().
            if (!$this->isElementAttached($xpath)) {
                throw $this->elementNotFound($xpath);
            }

            throw $e;
        }
    }

    #[\Override]
    public function mouseOver(string $xpath)
    {
        try {
            $this->driver->mouseOver($xpath);
        } catch (DriverException $e) {
            if (!$this->isActionTimeout($e)) {
                throw $e;
            }

            // Playwright refuses to hover a covered element and retries until the action timeout.
            // WebDriver moved the pointer anyway. The cover is often decoration that the test cannot remove,
            // for example a popover helper overlay or a flash message over the button.
            $this->hoverByPointer($xpath);
        }
    }

    /**
     * Moves the real pointer to the center of the element instead of sending events to the element.
     * A tooltip on an overlay that covers a disabled button appears only when the top node gets the event.
     * The moveto command of WebDriver worked in the same way.
     */
    private function hoverByPointer(string $xpath): void
    {
        $rect = $this->getElementRect($xpath);

        $this->getPage()->mouse()->move(
            $rect['x'] + $rect['width'] / 2,
            $rect['y'] + $rect['height'] / 2
        );
    }

    #[\Override]
    public function focus(string $xpath)
    {
        $this->driver->focus($xpath);
    }

    #[\Override]
    public function blur(string $xpath)
    {
        $this->driver->blur($xpath);
    }

    #[\Override]
    public function keyPress(string $xpath, $char, ?string $modifier = null)
    {
        $this->driver->keyPress($xpath, $char, $modifier);
    }

    #[\Override]
    public function keyUp(string $xpath, $char, ?string $modifier = null)
    {
        $this->driver->keyUp($xpath, $char, $modifier);
    }

    #[\Override]
    public function dragTo(string $sourceXpath, string $destinationXpath)
    {
        $this->driver->dragTo($sourceXpath, $destinationXpath);
    }

    #[\Override]
    public function executeScript(string $script)
    {
        if ($this->hasCurrentPageDialog()) {
            // an open dialog blocks the page JS. The skip replaces the UnexpectedAlertOpen error of WebDriver,
            // which the Oro hooks accept.
            return;
        }

        // BrowserTabManager::openTab() calls window.open() here and reads the new tab from getWindowNames() at once.
        // chromedriver registered the new handle before the script call returned. Playwright sends the "page" event
        // later, so without a wait the alias points at the old tab.
        $opensTab = false !== stripos($script, 'window.open');
        $pagesBefore = $opensTab ? count($this->getContext()->pages()) : 0;

        try {
            $this->driver->executeScript($script);
        } catch (DriverException $e) {
            $this->recoverIfBrowserCrashed($e);
            if (!$this->isNavigationRace($e)) {
                throw $e;
            }

            // the page navigated during the call, which is common in the @BeforeStep hooks after a click that starts
            // a navigation. The method waits for the new document and tries again one time.
            usleep(100000);
            $this->driver->executeScript($script);
        }

        $this->awaitNewPage($opensTab, $pagesBefore);
    }

    #[\Override]
    public function evaluateScript(string $script)
    {
        try {
            return $this->driver->evaluateScript($script);
        } catch (DriverException $e) {
            $this->recoverIfBrowserCrashed($e);
            if (!$this->isNavigationRace($e)) {
                throw $e;
            }

            usleep(100000);

            return $this->driver->evaluateScript($script);
        }
    }

    /**
     * Writes the recording of a context that the driver is about to discard.
     * saveTrace() usually runs on FeatureTested::AFTER, which a crash never gets to.
     * stop() drops the recording together with the dead context, and the archive at the end of the feature shows
     * only the replacement context. The method gives no guarantee, because a missing trace must not stop the recovery.
     */
    private function saveCrashTrace(): void
    {
        if (!$this->tracing || null === $this->crashTraceDir || null === $this->crashTraceLabel) {
            return;
        }

        try {
            if (
                !is_dir($this->crashTraceDir)
                && !@mkdir($this->crashTraceDir, 0777, true)
                && !is_dir($this->crashTraceDir)
            ) {
                return;
            }

            $path = sprintf(
                '%s/trace-%s-crash-%s.zip',
                rtrim($this->crashTraceDir, '/'),
                $this->crashTraceLabel,
                date('His')
            );

            if ($this->saveTrace($path)) {
                echo sprintf("Trace of the crashed browser: npx playwright show-trace %s\n", $path);
            }
        } catch (\Throwable) {
            // the crash artifact gives no guarantee, and the recovery continues in either case
        }
    }

    /**
     * Starts the browser again after a renderer crash, so that the crash fails only the current scenario.
     * A crash gives "Target crashed", or "Page crashed" during a navigation. The page object then stays dead,
     * and without a restart every later call of the feature fails. The step still fails with the crash error.
     */
    private function recoverIfBrowserCrashed(\Throwable $e): void
    {
        static $recovering = false;

        if ($recovering || !$this->isRendererCrash($e)) {
            return;
        }

        $recovering = true;
        try {
            $this->saveCrashTrace();
            try {
                $this->stop();
            } catch (\Throwable) {
                // the dead session can fail to close, and a new start follows
            }
            $this->start();
        } catch (\Throwable) {
            // the recovery gives no guarantee. The caller gives the original crash error again.
        } finally {
            $recovering = false;
        }

        throw new DriverException(
            sprintf('%s (the browser has been restarted after the crash)', $e->getMessage()),
            0,
            $e
        );
    }

    /**
     * Playwright reports a renderer crash as "Target crashed" in a protocol call, as "Page crashed" in goto,
     * and as "Navigation failed because page crashed!" in a load state wait.
     */
    private function isRendererCrash(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return false !== stripos($message, 'Target crashed') || false !== stripos($message, 'page crashed');
    }

    #[\Override]
    public function wait(int $timeout, string $condition)
    {
        if ($this->hasCurrentPageDialog()) {
            // an open dialog blocks the page JS, so the condition can never run.
            // Report success, so that the flow gets to the step that answers the dialog.
            return true;
        }

        // The wrapped driver builds "() => !!(<condition>)", which a semicolon at the end makes invalid.
        // The Oro IIFE snippets have such a semicolon.
        $condition = rtrim(trim($condition), ';');
        $deadline = microtime(true) + $timeout / 1000;

        try {
            while (true) {
                $remaining = (int)ceil(($deadline - microtime(true)) * 1000);
                if ($remaining <= 0) {
                    return false;
                }

                if ($this->driver->wait(min(self::CRASH_PROBE_INTERVAL, $remaining), $condition)) {
                    return true;
                }

                $this->assertRendererAlive();
            }
        } catch (DriverException $e) {
            $this->recoverIfBrowserCrashed($e);

            throw $e;
        }
    }

    /**
     * The wait of the wrapped driver hides "Target crashed" and reads a dead renderer until the timeout.
     * The step then reports the wait instead of the crash. locator::count() does not wait, so this probe is cheap.
     * The probe reports only a crash, because every other error belongs to a navigation in transit.
     */
    private function assertRendererAlive(): void
    {
        try {
            $this->getPage()->locator('xpath=/html')->count();
        } catch (\Throwable $e) {
            if ($this->isRendererCrash($e)) {
                throw new DriverException($e->getMessage(), 0, $e);
            }
        }
    }

    #[\Override]
    public function resizeWindow(int $width, int $height, ?string $name = null)
    {
        if ($this->emulatesDevice) {
            return;
        }

        $this->driver->resizeWindow($width, $height, $this->normalizeWindowName($name));
    }

    #[\Override]
    public function maximizeWindow(?string $name = null)
    {
        if ($this->emulatesDevice) {
            return;
        }

        $this->driver->maximizeWindow($this->normalizeWindowName($name));
    }

    #[\Override]
    public function submitForm(string $xpath)
    {
        $this->driver->submitForm($xpath);
    }

    /**
     * All cookies of the browser context in the WebDriver form of getWebDriverSession()->getAllCookies().
     * Each cookie has name, value, domain, path, secure and httpOnly, and sameSite when Playwright gives it.
     * A persistent cookie also has an integer "expiry".
     *
     * @return array<array<string, mixed>>
     */
    public function getAllCookies(): array
    {
        $cookies = [];
        foreach ($this->getContext()->cookies() as $cookie) {
            $mapped = [
                'name' => (string)($cookie['name'] ?? ''),
                'value' => (string)($cookie['value'] ?? ''),
                'path' => (string)($cookie['path'] ?? '/'),
                'domain' => (string)($cookie['domain'] ?? ''),
                'secure' => (bool)($cookie['secure'] ?? false),
                'httpOnly' => (bool)($cookie['httpOnly'] ?? false),
            ];
            if (isset($cookie['sameSite'])) {
                $mapped['sameSite'] = $cookie['sameSite'];
            }
            $expires = $cookie['expires'] ?? -1;
            if (is_numeric($expires) && $expires > 0) {
                $mapped['expiry'] = (int)$expires;
            }

            $cookies[] = $mapped;
        }

        return $cookies;
    }

    /**
     * Message of the open JS dialog, or null when no dialog opens before the timeout.
     * The dialog stays open, as with the getAlert_text() command of WebDriver, until acceptAlert() or dismissAlert().
     */
    public function getAlertMessage(int $timeoutMs = 3000): ?string
    {
        return $this->waitForDialog($timeoutMs)?->message();
    }

    /**
     * @return bool TRUE when a dialog was open and has been accepted
     */
    public function acceptAlert(?string $promptText = null, int $timeoutMs = 3000): bool
    {
        $dialog = $this->waitForDialog($timeoutMs);
        if (null === $dialog) {
            return false;
        }

        try {
            $dialog->accept($promptText);
        } catch (\Throwable) {
            return false;
        } finally {
            $this->forgetDialog($dialog);
        }

        return true;
    }

    /**
     * @return bool TRUE when a dialog was open and has been dismissed
     */
    public function dismissAlert(int $timeoutMs = 3000): bool
    {
        $dialog = $this->waitForDialog($timeoutMs);
        if (null === $dialog) {
            return false;
        }

        try {
            $dialog->dismiss();
        } catch (\Throwable) {
            return false;
        } finally {
            $this->forgetDialog($dialog);
        }

        return true;
    }

    /**
     * Deletes the session cookies to emulate a browser restart. Playwright marks a session cookie with expires = -1.
     */
    public function deleteSessionCookies(): void
    {
        $context = $this->getContext();
        foreach ($context->cookies() as $cookie) {
            $expires = $cookie['expires'] ?? -1;
            if (is_numeric($expires) && $expires < 0 && isset($cookie['name'])) {
                $context->deleteCookie((string)$cookie['name']);
            }
        }
    }

    /**
     * Drags the source element and drops it on the target with trusted mouse events. OroMainContext uses the WebDriver
     * sequence moveto, buttondown, moveto and buttonup for Selenium. The offsets follow the WebDriver moveto rules.
     * With a destination and offsets, the offsets are relative to the top left corner of the destination.
     * With a destination and no offsets, the drop point is the center of the destination.
     * Without a destination, the offsets are relative to the current pointer position.
     */
    public function dragAndDropWithOffsets(
        string $sourceXpath,
        ?string $destinationXpath,
        ?int $xOffset = null,
        ?int $yOffset = null
    ): void {
        $mouse = $this->getMouse();
        $source = $this->getElementRect($sourceXpath);

        $startX = $source['x'] + $source['width'] / 2;
        $startY = $source['y'] + $source['height'] / 2;

        $mouse->move($startX, $startY);
        $mouse->down();
        // small initial move so drag libraries recognize the gesture
        $mouse->move($startX - 1, $startY - 1, ['steps' => 2]);

        if (null !== $destinationXpath) {
            $destination = $this->getElementRect($destinationXpath);
            if (null !== $xOffset || null !== $yOffset) {
                $endX = $destination['x'] + ($xOffset ?? 0);
                $endY = $destination['y'] + ($yOffset ?? 0);
            } else {
                $endX = $destination['x'] + $destination['width'] / 2;
                $endY = $destination['y'] + $destination['height'] / 2;
            }
        } else {
            $endX = $startX - 1 + ($xOffset ?? 0);
            $endY = $startY - 1 + ($yOffset ?? 0);
        }

        $mouse->move($endX, $endY, ['steps' => 10]);
        $mouse->up();
    }

    /**
     * TRUE when the error is an actionability stall of playwright-mink, of Playwright or of the JSON-RPC transport.
     * The transport stops first only on a page without the timeouts of applyDefaultTimeouts().
     * There the Playwright default of 30 s equals the transport limit.
     */
    private function isActionTimeout(DriverException $e): bool
    {
        $message = $e->getMessage();

        return false !== stripos($message, 'timeout')
            || false !== stripos($message, 'timed out')
            || false !== stripos($message, 'not actionable');
    }

    private function isNavigationRace(DriverException $e): bool
    {
        return str_contains($e->getMessage(), 'Execution context was destroyed')
            || str_contains($e->getMessage(), 'Cannot find context with specified id');
    }

    /**
     * Keeps the dialogs that the page opens until a step answers them with acceptAlert() or dismissAlert().
     * The Node bridge always subscribes to the dialog event, so Playwright never closes a dialog on its own.
     * A beforeunload confirm is accepted at once, as WebDriver did, because no step expects it.
     * A native dialog without an answer blocks every page operation until the JSON-RPC transport times out.
     */
    private function installDialogListener(): void
    {
        try {
            $page = $this->getPage();
            // The event emitter of a cached page keeps every listener. A second listener queues each dialog twice.
            if (isset($this->dialogListenerPages[$page])) {
                return;
            }

            $page->events()->onDialog(function (DialogInterface $dialog): void {
                if ('beforeunload' === $dialog->type()) {
                    try {
                        $dialog->accept();
                    } catch (\Throwable) {
                    }

                    return;
                }

                $this->pendingDialogs[] = $dialog;
            });
            $this->dialogListenerPages[$page] = true;
        } catch (\Throwable) {
        }
    }

    /**
     * A dialog belongs to one tab and blocks only the JS of that tab, as in WebDriver.
     * The page of a dialog is the cached page object of the browser context, so an identity check is enough.
     *
     * @return DialogInterface[]
     */
    private function getCurrentPageDialogs(): array
    {
        try {
            $page = $this->getPage();
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter(
            $this->pendingDialogs,
            static function (DialogInterface $dialog) use ($page): bool {
                try {
                    return $dialog->page() === $page;
                } catch (\Throwable) {
                    return false;
                }
            }
        ));
    }

    private function hasCurrentPageDialog(): bool
    {
        return [] !== $this->getCurrentPageDialogs();
    }

    /**
     * @param DialogInterface[] $dialogs
     */
    private function dismissDialogs(array $dialogs): void
    {
        foreach ($dialogs as $dialog) {
            try {
                $dialog->dismiss();
            } catch (\Throwable) {
                // the dialog can belong to a page that is already closed
            }
            $this->forgetDialog($dialog);
        }
    }

    private function forgetDialog(DialogInterface $dialog): void
    {
        $this->pendingDialogs = array_values(array_filter(
            $this->pendingDialogs,
            static fn (DialogInterface $pending): bool => $pending !== $dialog
        ));
    }

    /**
     * Returns the oldest dialog without an answer and keeps it in the buffer.
     * The getCurrentUrl() request makes the transport read the dialog events without the page JS.
     */
    private function waitForDialog(int $timeoutMs): ?DialogInterface
    {
        $deadline = microtime(true) + $timeoutMs / 1000;

        while (true) {
            $dialogs = $this->getCurrentPageDialogs();
            if ($dialogs) {
                return $dialogs[0];
            }
            if (microtime(true) >= $deadline) {
                return null;
            }

            try {
                $this->driver->getCurrentUrl();
            } catch (\Throwable) {
            }
            usleep(100000);
        }
    }

    /**
     * Restores the textarea content that the text() of WebDriver included.
     */
    private function appendTextareaValues(string $xpath, string $text): string
    {
        try {
            $values = $this->evaluateOnXpathWithoutWaiting(
                $xpath,
                'const areas = ({{ELEMENT}}).tagName === \'TEXTAREA\''
                . '   ? [({{ELEMENT}})]'
                . '   : (({{ELEMENT}}).querySelectorAll'
                . '     ? Array.from(({{ELEMENT}}).querySelectorAll(\'textarea\'))'
                . '     : []);'
                . ' return areas'
                . '   .filter(a => !a.checkVisibility || a.checkVisibility())'
                . '   .map(a => a.value)'
                . '   .filter(v => typeof v === \'string\' && v.trim() !== \'\');'
            );
        } catch (\Throwable) {
            return $text;
        }

        if (!is_array($values)) {
            return $text;
        }

        foreach ($values as $value) {
            if (is_string($value) && !str_contains($text, $value)) {
                $text .= "\n" . $value;
            }
        }

        return $text;
    }

    /**
     * NULL when the visibility is unknown, for example for a missing element.
     * The callers then use the wrapped driver, which gives the standard error.
     */
    private function isElementVisible(string $xpath): ?bool
    {
        try {
            return $this->driver->isVisible($xpath);
        } catch (\Throwable) {
            return null;
        }
    }

    private function clickByJs(string $xpath): void
    {
        // element.click() alone does not reach a widget that selects on mousedown or mouseup, for example select2 v3.
        // WebDriver sent the full pointer sequence, and this code does the same.
        $this->executeJsOnXpath(
            $xpath,
            '({{ELEMENT}}).scrollIntoView({block: "center"});
            const rect = ({{ELEMENT}}).getBoundingClientRect();
            const opts = {
                bubbles: true,
                cancelable: true,
                view: window,
                clientX: rect.x + rect.width / 2,
                clientY: rect.y + rect.height / 2,
                button: 0,
                buttons: 1
            };
            for (const type of ["pointerdown", "mousedown", "pointerup", "mouseup"]) {
                ({{ELEMENT}}).dispatchEvent(
                    type.startsWith("pointer") ? new PointerEvent(type, opts) : new MouseEvent(type, opts)
                );
            }
            ({{ELEMENT}}).click();'
        );
    }

    /**
     * NULL when the value is not boolean-like by the Selenium2Driver rules.
     */
    private function toBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value) && in_array(strtolower($value), ['true', 'false', '1', '0', 'on', 'yes', 'no'], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return null;
    }

    /**
     * Sets or clears a checkbox, or selects a radio, also when Oro hides the input behind a styled widget.
     * A hidden input goes directly to the JS path. A visible input that stalls gets it after the action timeout.
     */
    private function setCheckedState(string $xpath, string $type, bool $value): void
    {
        if (false === $this->isElementVisible($xpath)) {
            $this->setCheckedByJs($xpath, $value);

            return;
        }

        try {
            if ('radio' === $type) {
                // Selenium2Driver rule: a boolean selects the radio by its converted value
                $this->driver->setValue($xpath, $value ? '1' : '0');
            } elseif ($value) {
                $this->driver->check($xpath);
            } else {
                $this->driver->uncheck($xpath);
            }
        } catch (DriverException $e) {
            if (!$this->isActionTimeout($e)) {
                throw $e;
            }

            $this->setCheckedByJs($xpath, $value);
        }
    }

    /**
     * A DOM click changes the state and sends the change event, also on a hidden input.
     * A direct write of the property is the last option, for an input whose click handlers stop the change.
     */
    private function setCheckedByJs(string $xpath, bool $value): void
    {
        $script = sprintf(
            'if (({{ELEMENT}}).checked !== %1$s) {
                ({{ELEMENT}}).click();
            }
            if (({{ELEMENT}}).checked !== %1$s) {
                ({{ELEMENT}}).checked = %1$s;
                ({{ELEMENT}}).dispatchEvent(new Event("input", { bubbles: true }));
                ({{ELEMENT}}).dispatchEvent(new Event("change", { bubbles: true }));
            }',
            $value ? 'true' : 'false'
        );

        $this->executeJsOnXpath($xpath, $script);
    }

    private function selectOptionByJs(string $xpath, string $value, bool $multiple): void
    {
        $script = sprintf(
            'const value = %s;
            const options = Array.from(({{ELEMENT}}).options || []);
            const match = options.find((o) => o.value === value)
                || options.find((o) => (o.textContent || "").trim() === value.trim());
            if (!match) {
                throw new Error("No option \"" + value + "\" in select");
            }
            if (!%s) {
                options.forEach((o) => { o.selected = false; });
            }
            match.selected = true;
            ({{ELEMENT}}).dispatchEvent(new Event("input", { bubbles: true }));
            ({{ELEMENT}}).dispatchEvent(new Event("change", { bubbles: true }));',
            json_encode($value),
            $multiple ? 'true' : 'false'
        );

        $this->executeJsOnXpath($xpath, $script);
    }

    /**
     * @return array{x: float, y: float, width: float, height: float}
     */
    private function getElementRect(string $xpath): array
    {
        $rect = $this->executeJsOnXpath(
            $xpath,
            '({{ELEMENT}}).scrollIntoView({block: "center", behavior: "instant"});
            const r = ({{ELEMENT}}).getBoundingClientRect();
            return { x: r.x, y: r.y, width: r.width, height: r.height };'
        );

        if (!is_array($rect)) {
            throw new DriverException(sprintf('Unable to determine position of the element "%s"', $xpath));
        }

        return $rect;
    }

    private function getPageId(PageInterface $page): string
    {
        return (new \ReflectionProperty($page, 'pageId'))->getValue($page);
    }

    /**
     * Changes the wrapped driver to the given page. It then applies the page setup again, which is the default
     * timeouts, the dialog listener and the JS error collector.
     */
    private function setVendorPage(PageInterface $page): void
    {
        $this->setVendorProperty('page', $page);
        $this->setVendorProperty('frameScope', null);

        $this->applyDefaultTimeouts();
        $this->installDialogListener();
        $this->installJsErrorCollector();
    }

    /**
     * A cheap request makes the transport read the queued events, such as new pages and dialogs.
     */
    private function pumpTransportEvents(): void
    {
        try {
            $this->driver->getCurrentUrl();
        } catch (\Throwable) {
        }
    }

    private function getMouse(): Mouse
    {
        $page = $this->getPage();
        $reflection = new \ReflectionObject($page);

        return new Mouse(
            $reflection->getProperty('transport')->getValue($page),
            $reflection->getProperty('pageId')->getValue($page)
        );
    }

    /**
     * WebDriver accepts "current" as the name of the active window.
     * Playwright has no such name, and to stay on the active page is the equivalent.
     */
    private function normalizeWindowName(?string $name): ?string
    {
        return 'current' === $name ? null : $name;
    }

    private function getPage(): PageInterface
    {
        $property = new \ReflectionProperty(PlaywrightDriver::class, 'page');

        return $property->getValue($this->driver);
    }

    private function getContext(): BrowserContextInterface
    {
        $property = new \ReflectionProperty(PlaywrightDriver::class, 'context');

        return $property->getValue($this->driver);
    }

    /**
     * Installs a collector of browser console errors for each document, which getCollectedJsErrors() reads.
     * An init script runs before every page script on every navigation, so the buffer is always there.
     * An error in the collector never stops the test run.
     */
    private function installJsErrorCollector(): void
    {
        $script = <<<'JS'
            (() => {
                const buffer = [];
                window.__oroBehatJsErrors = buffer;
                const push = (type, message) => {
                    if (buffer.length >= 50) {
                        return;
                    }
                    buffer.push({ type: type, message: String(message), time: new Date().toISOString() });
                };
                window.addEventListener('error', (e) => {
                    push('error', e.message + (e.filename ? ' at ' + e.filename + ':' + e.lineno : ''));
                });
                window.addEventListener('unhandledrejection', (e) => {
                    push('unhandledrejection', e.reason && e.reason.stack ? e.reason.stack : e.reason);
                });
                const originalError = console.error;
                console.error = function (...args) {
                    push('console.error', args.map((a) => {
                        if (a instanceof Error) {
                            return a.stack || a.message;
                        }
                        try {
                            return typeof a === 'string' ? a : JSON.stringify(a);
                        } catch (err) {
                            return String(a);
                        }
                    }).join(' '));
                    return originalError.apply(console, args);
                };
            })();
            JS;

        try {
            $this->getContext()->addInitScript($script);
        } catch (\Throwable) {
        }
    }

    /**
     * @param array{file: string, line: int}|null $location
     */
    private function openTraceGroup(string $name, ?array $location = null): void
    {
        $command = ['action' => 'tracingGroup', 'name' => $name];
        if (null !== $location) {
            // the bridge puts the value into {location: {file: ...}}, and it supports the file only
            $command['location'] = $location['file'];
        }
        $this->sendContextCommand($command);
    }

    private function closeAllTraceGroups(): void
    {
        if ($this->traceStepGroupOpen) {
            $this->traceStepGroupOpen = false;
            $this->sendContextCommand(['action' => 'tracingGroupEnd']);
        }

        if ($this->traceScenarioGroupOpen) {
            $this->traceScenarioGroupOpen = false;
            $this->sendContextCommand(['action' => 'tracingGroupEnd']);
        }
    }

    /**
     * Sends a raw command to the browser context of the wrapped driver.
     * The Playwright PHP interfaces do not give the transport, so the method uses reflection.
     */
    private function sendContextCommand(array $command): void
    {
        $context = $this->getContext();
        $reflection = new \ReflectionObject($context);
        $transport = $reflection->getProperty('transport')->getValue($context);
        $contextId = $reflection->getProperty('contextId')->getValue($context);

        $transport->send($command + ['contextId' => $contextId]);
    }

    /**
     * Sends a raw command to the active page of the wrapped driver and returns the transport response.
     * It uses reflection for the same reason as sendContextCommand().
     */
    private function sendPageCommand(array $command): array
    {
        $page = $this->getPage();
        $reflection = new \ReflectionObject($page);
        $transport = $reflection->getProperty('transport')->getValue($page);
        $pageId = $reflection->getProperty('pageId')->getValue($page);

        $response = $transport->send($command + ['pageId' => $pageId]);

        return is_array($response) ? $response : [];
    }

    /**
     * Resolves the xpath inside the current frame scope, as the locator() method of the wrapped driver does.
     * Without the frame scope, keyDown(), typeIntoInput(), executeJsOnXpath() and clickByJs() work on the main page
     * without a message while a step is inside an iframe.
     */
    private function locator(string $xpath): LocatorInterface
    {
        $selector = 'xpath=' . $xpath;
        $frameScope = $this->getFrameScope();

        return $frameScope
            ? $frameScope->locator($selector)->first()
            : $this->getPage()->locator($selector)->first();
    }

    private function getFrameScope(): ?FrameLocatorInterface
    {
        $property = new \ReflectionProperty(PlaywrightDriver::class, 'frameScope');

        return $property->getValue($this->driver);
    }

    /**
     * TRUE while the element is still attached to the DOM.
     * locator::count() is one of the locator calls that do not wait for the selector.
     */
    private function isElementAttached(string $xpath): bool
    {
        try {
            return $this->locator($xpath)->count() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * A read of a detached element fails at once, as with the StaleElementReferenceException of WebDriver.
     * Thus the spin loops around such a read ask the DOM again on the next tick.
     * A locator read would wait for the selector until the action timeout. That wait blocks the scenario,
     * and it hides a short-lived UI part, such as a flash message.
     */
    private function assertElementAttached(string $xpath): void
    {
        if (!$this->isElementAttached($xpath)) {
            throw $this->elementNotFound($xpath);
        }
    }

    /**
     * WebDriver answered every operation on a missing xpath with NoSuchElement. The shared Behat contexts branch on
     * that class, not on a Mink DriverException. Examples are FilterContext::setFilterValue(),
     * Grid::hasMassActionLink() and OroMainContext::assertElementNotOnPage().
     */
    private function elementNotFound(string $xpath): NoSuchElement
    {
        return new NoSuchElement(sprintf('Element with xpath "%s" is not attached to the DOM', $xpath));
    }

    private function setValueByJsWithEvents(string $xpath, string $value): void
    {
        $this->locator($xpath)->evaluate(
            '(el, value) => {
                el.value = value;
                el.dispatchEvent(new KeyboardEvent("keyup", { bubbles: true }));
                el.dispatchEvent(new Event("change", { bubbles: true }));
            }',
            $value
        );
    }

    /**
     * @return bool TRUE when the given element is TinyMCE, otherwise FALSE
     */
    private function fillTinyMce(string $xpath, string $value): bool
    {
        $fieldId = $this->getAttribute($xpath, 'id');

        if (!$fieldId) {
            return false;
        }

        $isTinyMce = $this->evaluateScript(
            sprintf('typeof tinyMCE !== "undefined" && null != tinyMCE.get("%s");', $fieldId)
        );

        if (!$isTinyMce) {
            return false;
        }

        $this->executeScript(
            sprintf('tinyMCE.get("%s").setContent(%s);', $fieldId, json_encode($value))
        );

        return true;
    }
}
