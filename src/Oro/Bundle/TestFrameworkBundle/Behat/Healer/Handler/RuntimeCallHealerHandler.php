<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Healer\Handler;

use Behat\Testwork\Argument\Validator;
use Behat\Testwork\Call\Call;
use Behat\Testwork\Call\CallResult;
use Behat\Testwork\Call\Exception\CallErrorException;
use Behat\Testwork\Call\Handler\CallHandler;
use Oro\Bundle\TestFrameworkBundle\Behat\Healer\HealerProcessor;

/**
 * A copy of {@see \Behat\Testwork\Call\Handler\RuntimeCallHandler} that calls healers
 * when call handing results to exception.
 */
class RuntimeCallHealerHandler implements CallHandler
{
    private bool $obStarted = false;
    private Validator $validator;
    private ?HealerProcessor $healerProcessor = null;

    public function __construct(private readonly ?int $errorReportingLevel = E_ALL)
    {
        $this->validator = new Validator();
    }

    public function setHealerProcessor(HealerProcessor $healerProcessor): void
    {
        $this->healerProcessor = $healerProcessor;
    }

    public function supportsCall(Call $call): bool
    {
        return true;
    }

    public function handleCall(Call $call): CallResult
    {
        $this->startErrorAndOutputBuffering($call);
        $result = $this->executeCall($call);
        /** Customization start */
        // Healing process
        if (null !== $result->getException()) {
            $result = $this->healerProcessor->handleCall(
                $call,
                $result,
                [$this, 'executeCall']
            );
        }
        /** Customization end */
        $this->stopErrorAndOutputBuffering();

        return $result;
    }

    /**
     * Used as a custom error handler when step is running.
     *
     * @throws CallErrorException
     */
    public function handleError(int $level, string $message, string $file, int $line): bool
    {
        if ($this->errorLevelIsNotReportable($level)) {
            return false;
        }

        throw new CallErrorException($level, $message, $file, $line);
    }

    public function executeCall(Call $call): CallResult
    {
        $reflection = $call->getCallee()->getReflection();
        $callable = $call->getBoundCallable();
        $arguments = $call->getArguments();
        $return = $exception = null;

        try {
            $arguments = array_values($arguments);
            $this->validator->validateArguments($reflection, $arguments);
            $return = $callable(...$arguments);
        } catch (\Exception $caught) {
            $exception = $caught;
        }

        $stdOut = $this->getBufferedStdOut();

        return new CallResult($call, $return, $exception, $stdOut);
    }

    private function getBufferedStdOut(): ?string
    {
        return ob_get_length() ? ob_get_contents() : null;
    }

    private function startErrorAndOutputBuffering(Call $call): void
    {
        $errorReporting = $call->getErrorReportingLevel() ?: $this->errorReportingLevel;
        set_error_handler(array($this, 'handleError'), $errorReporting);
        $this->obStarted = ob_start();
    }

    private function stopErrorAndOutputBuffering(): void
    {
        if ($this->obStarted) {
            ob_end_clean();
        }
        restore_error_handler();
    }

    private function errorLevelIsNotReportable(int $level): bool
    {
        return !(error_reporting() & $level);
    }
}
