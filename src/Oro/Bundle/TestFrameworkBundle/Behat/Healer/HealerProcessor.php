<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Healer;

use Behat\Testwork\Call\Call;
use Behat\Testwork\Call\CallResult;
use Behat\Testwork\Output\Formatter;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Behat\Healer\Event\AfterHealerEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Processor is trying to heal failed behat steps.
 */
class HealerProcessor
{
    private const string FORMATTER_STYLE = 'skipped';
    private const string FORMATER_RETREAT = '    ';

    private readonly Stopwatch $stopwatch;

    public function __construct(
        private readonly Formatter $formatter,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly iterable $healers,
    ) {
        $this->stopwatch = new Stopwatch();
    }

    public function handleCall(Call $call, CallResult $failedResult, array|callable $callCallback): CallResult
    {
        if (!$this->isSupports($call)) {
            return $failedResult;
        }
        $healingId = UUIDGenerator::v4();
        $this->writeOutput(sprintf('Step: %s is failed', $call->getStep()->getText()), 'failed');
        $this->writeOutput('Trying to heal the step');
        /** @var HealerInterface $healer */
        foreach ($this->healers as $healer) {
            if (!$healer->supports($call)) {
                continue;
            }
            $this->writeOutput(sprintf('%s  # %s', $healer->getLabel(), $healer::class));
            $this->stopwatch->start($healingId);

            $isProcessed = $healer->process($call, $failedResult);
            $this->stopwatch->stop($healingId);
            $healerExecTime = $this->stopwatch->getEvent($healingId)->getDuration() / 1000;
            if (!$isProcessed) {
                $this->afterHealer($healer, $call, $failedResult, $healingId, $healerExecTime);
                $this->writeOutput(sprintf('%s - unsuccessfully # %s', $healer->getLabel(), $healer::class));
                continue;
            }
            /** @var CallResult $result */
            $result = $callCallback($call);
            if (null === $result->getException()) {
                $this->writeOutput('Step healed successfully');
                $this->afterHealer($healer, $call, $result, $healingId, $healerExecTime);

                if ($healer->fallInAnyResult()) {
                    return $failedResult;
                }

                return $result;
            }
            $this->afterHealer($healer, $call, $failedResult, $healingId, $healerExecTime);
        }
        $this->writeOutput('Step failed', 'failed');

        return $failedResult;
    }

    protected function isSupports(Call $call): bool
    {
        foreach ($this->healers as $healer) {
            if ($healer->supports($call)) {
                return true;
            }
        }

        return false;
    }

    private function afterHealer(
        HealerInterface $healer,
        Call $call,
        CallResult $failedResult,
        string $healingId,
        float $time
    ): void {
        $afterHealerEvent = new AfterHealerEvent($healer, $call, $failedResult, $healingId, $time);
        // dispatch event after healer processing
        $this->eventDispatcher->dispatch($afterHealerEvent);
    }

    protected function writeOutput(string $text, string $style = self::FORMATTER_STYLE): void
    {
        $this->formatter->getOutputPrinter()->writeln(
            sprintf(
                '%s{+%s}%s{-%s}',
                self::FORMATER_RETREAT,
                $style,
                $text,
                $style
            )
        );
    }
}
