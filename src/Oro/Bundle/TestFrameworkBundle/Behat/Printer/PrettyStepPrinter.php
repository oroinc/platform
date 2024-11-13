<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Printer;

use Behat\Behat\Output\Node\Printer\Helper\ResultToStringConverter;
use Behat\Behat\Output\Node\Printer\Helper\StepTextPainter;
use Behat\Behat\Output\Node\Printer\Pretty\PrettyPathPrinter;
use Behat\Behat\Output\Node\Printer\StepPrinter;
use Behat\Behat\Tester\Result\DefinedStepResult;
use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Gherkin\Node\ArgumentInterface;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\ScenarioLikeInterface as Scenario;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\Exception\ExceptionPresenter;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Tester\Result\ExceptionResult;
use Oro\Bundle\TestFrameworkBundle\Behat\Session\Mink\WatchModeSessionHolder;

/**
 * A copy of {@see \Behat\Behat\Output\Node\Printer\Pretty\PrettyStepPrinter} that decorated for "--watch" mode.
 */
class PrettyStepPrinter implements StepPrinter
{
    private string $indentText;
    private string $subIndentText;

    /**
     * Initializes printer.
     */
    public function __construct(
        private readonly StepTextPainter $textPainter,
        private readonly ResultToStringConverter $resultConverter,
        private readonly PrettyPathPrinter $pathPrinter,
        private readonly ExceptionPresenter $exceptionPresenter,
        private readonly WatchModeSessionHolder $sessionHolder,
        $indentation = 4,
        $subIndentation = 2
    ) {
        $this->indentText = str_repeat(' ', intval($indentation));
        $this->subIndentText = $this->indentText . str_repeat(' ', intval($subIndentation));
    }

    /**
     * {@inheritdoc}
     */
    public function printStep(Formatter $formatter, Scenario $scenario, StepNode $step, StepResult $result): void
    {
        /** Customization start */
        $this->printText(
            $formatter->getOutputPrinter(),
            $step->getLine(),
            $step->getKeyword(),
            $step->getText(),
            $result
        );
        /** Customization end */
        $this->pathPrinter->printStepPath($formatter, $scenario, $step, $result, mb_strlen($this->indentText, 'utf8'));
        $this->printArguments($formatter, $step->getArguments(), $result);
        $this->printStdOut($formatter->getOutputPrinter(), $result);
        $this->printException($formatter->getOutputPrinter(), $result);
    }

    private function printText(
        OutputPrinter $printer,
        int $stepLine,
        string $stepType,
        string $stepText,
        StepResult $result
    ): void {
        if ($result instanceof DefinedStepResult && $result->getStepDefinition()) {
            $definition = $result->getStepDefinition();
            $stepText = $this->textPainter->paintText($stepText, $definition, $result);
        }

        $style = $this->resultConverter->convertResultToString($result);
        /** Customization start */
        if ($this->sessionHolder->isWatchFrom() || $this->sessionHolder->isWatchMode()) {
            // print test step line before text while --watch mode is enabled
            $printer->write(
                sprintf(
                    '%s{+%s}%s %s %s{-%s}',
                    $this->indentText,
                    $style,
                    $stepLine,
                    $stepType,
                    $stepText,
                    $style
                )
            );
        } else {
            $printer->write(
                sprintf(
                    '%s{+%s}%s %s{-%s}',
                    $this->indentText,
                    $style,
                    $stepType,
                    $stepText,
                    $style
                )
            );
        }
        /** Customization end */
    }

    /**
     * Prints step multiline arguments.
     */
    private function printArguments(Formatter $formatter, array $arguments, StepResult $result): void
    {
        $style = $this->resultConverter->convertResultToString($result);

        foreach ($arguments as $argument) {
            $text = $this->getArgumentString($argument, !$formatter->getParameter('multiline'));

            $indentedText = implode("\n", array_map(array($this, 'subIndent'), explode("\n", $text)));
            $formatter->getOutputPrinter()->writeln(sprintf('{+%s}%s{-%s}', $style, $indentedText, $style));
        }
    }

    /**
     * Prints step output (if has one).
     */
    private function printStdOut(OutputPrinter $printer, StepResult $result): void
    {
        if (!$result instanceof ExecutedStepResult || null === $result->getCallResult()->getStdOut()) {
            return;
        }

        $callResult = $result->getCallResult();
        $indentedText = $this->subIndentText;

        $pad = function ($line) use ($indentedText) {
            return sprintf(
                '%s│ {+stdout}%s{-stdout}',
                $indentedText,
                $line
            );
        };

        $printer->writeln(implode("\n", array_map($pad, explode("\n", $callResult->getStdOut()))));
    }

    /**
     * Prints step exception (if has one).
     */
    private function printException(OutputPrinter $printer, StepResult $result): void
    {
        $style = $this->resultConverter->convertResultToString($result);

        if (!$result instanceof ExceptionResult || !$result->hasException()) {
            return;
        }

        $text = $this->exceptionPresenter->presentException($result->getException());
        $indentedText = implode("\n", array_map(array($this, 'subIndent'), explode("\n", $text)));
        $printer->writeln(sprintf('{+%s}%s{-%s}', $style, $indentedText, $style));
    }

    /**
     * Returns argument string for provided argument.
     */
    private function getArgumentString(ArgumentInterface $argument, $collapse = false): string
    {
        if ($collapse) {
            return '...';
        }

        if ($argument instanceof PyStringNode) {
            return '"""' . "\n" . $argument . "\n" . '"""';
        }

        return (string) $argument;
    }

    /**
     * Indents text to the subIndentation level.
     */
    private function subIndent(string $text): string
    {
        return $this->subIndentText . $text;
    }
}
