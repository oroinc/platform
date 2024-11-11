<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Healer;

use Behat\Testwork\Call\Call;
use Behat\Testwork\Call\CallResult;
use Behat\Testwork\Output\Formatter;

/**
 * Processor is trying to heal failed behat steps.
 */
class HealerProcessor
{
    private const string FORMATTER_STYLE = 'skipped';
    private const string FORMATER_RETREAT = '    ';

    private iterable $healers = [];

    public function __construct(private readonly Formatter $formatter)
    {
    }

    /**
     * @param iterable|HealerInterface[] $healers
     */
    public function setHealers(iterable $healers): void
    {
        $this->healers = $healers;
    }

    public function handleCall(Call $call, CallResult $failedResult, array|callable $callCallback): CallResult
    {
        $this->writeOutput(sprintf('Step: %s is failed', $call->getStep()->getText()), 'failed');
        $this->writeOutput('Trying to heal the step');
        foreach ($this->healers as $healer) {
            if (!$healer->supports($call)) {
                continue;
            }
            $this->writeOutput(sprintf('%s  # %s', $healer->getLabel(), $healer::class));

            $isProcessed = $healer->process($call, $failedResult);
            if (!$isProcessed) {
                $this->writeOutput(sprintf('%s - unsuccessfully # %s', $healer->getLabel(), $healer::class));
                continue;
            }
            /** @var CallResult $result */
            $result = $callCallback($call);
            if (null === $result->getException()) {
                $this->writeOutput('Step healed successfully');
                if ($healer->fallInAnyResult()) {
                    return $failedResult;
                }

                return $result;
            }
        }
        $this->writeOutput('Step failed', 'failed');

        return $failedResult;
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
