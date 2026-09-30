<?php

declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Process\Process;

/**
 * Prepares the Playwright driver of the Behat tests after a composer install or update.
 *
 * - When ORO_BEHAT_PLAYWRIGHT is not true, it does nothing, so an ordinary installation downloads no browser.
 * - Otherwise it runs the playwright-bootstrap script of this bundle. The script does nothing when
 *   playwright-php/playwright is not installed. A script failure stops the composer install or update.
 *
 * ORO_BEHAT_PLAYWRIGHT also switches the Behat sessions to Playwright, see OroTestFrameworkExtension.
 * The oro/platform composer.json declares it in extra.oro-post-install-cmd and extra.oro-post-update-cmd,
 * which {@see \Oro\Bundle\InstallerBundle\Composer\ScriptHandler} runs for every application.
 */
class PlaywrightBootstrapHandler
{
    private const string ENABLED_ENV = 'ORO_BEHAT_PLAYWRIGHT';

    public static function bootstrap(Event $event): void
    {
        if (!static::isEnabled()) {
            return;
        }

        $config = $event->getComposer()->getConfig();
        $appDir = \dirname($config->get('vendor-dir'));
        $exitCode = static::runProcess(
            $event->getIO(),
            ['bash', static::getScriptPath(), $appDir],
            max(0, (int)$config->get('process-timeout')),
            $appDir
        );

        if (0 !== $exitCode) {
            throw new \RuntimeException(sprintf(
                'The Playwright bootstrap failed with the exit code %d. %s has a true value, so the'
                . ' Behat tests need the Playwright driver. See the output above.',
                $exitCode,
                self::ENABLED_ENV
            ));
        }
    }

    protected static function isEnabled(): bool
    {
        $value = getenv(self::ENABLED_ENV);

        return false !== $value && '' !== $value && filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected static function getScriptPath(): string
    {
        return \dirname(__DIR__) . '/Resources/bin/playwright-bootstrap';
    }

    /**
     * A seam that runs the command and returns its exit code.
     * Tests/Unit/Stub/TestablePlaywrightBootstrapHandler overrides it, so the unit tests start no process.
     */
    protected static function runProcess(IOInterface $inputOutput, array $cmd, int $timeout, string $cwd): int
    {
        $inputOutput->write(implode(' ', $cmd));

        $process = new Process($cmd, $cwd, null, null, $timeout);
        $process->run(function ($outputType, string $data) use ($inputOutput) {
            if ($outputType === Process::OUT) {
                $inputOutput->write($data, false);
            } else {
                $inputOutput->writeError($data, false);
            }
        });

        return (int)$process->getExitCode();
    }
}
