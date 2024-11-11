<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Provider;

use Behat\Gherkin\Loader\AbstractFileLoader;
use Behat\Gherkin\Node\FeatureNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Exception\SkippTestExecutionException;
use Oro\Bundle\TestFrameworkBundle\Behat\Session\Mink\WatchModeSessionHolder;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Question\Question;

/**
 * Provides a methods to ask a question which used on behat --watch mode.
 */
readonly class WatchModeQuestionProvider
{
    public function __construct(
        private WatchModeSessionHolder $sessionHolder,
        private AbstractFileLoader $fileLoader,
    ) {
    }

    public function askInputStepLine(FeatureNode $feature): int
    {
        $lastStepLine = $this->sessionHolder->getLastProcessedStep();
        $question = new Question(
            sprintf(
                'Press ENTER to continue from the current line #%d, or enter the line number to continue '
                . '(Ctrl+C to exit): ',
                $lastStepLine
            ),
            $lastStepLine
        );
        $question->setAutocompleterCallback(function ($input) use ($feature, $lastStepLine) {
            return $this->getAutocompleteSteps($feature, $lastStepLine, true);
        });
        $question->setValidator(function ($passedLine) use ($feature, $lastStepLine) {
            if (empty($passedLine)) {
                return $lastStepLine;
            }
            $currentSteps = $this->getAutocompleteSteps($feature, $lastStepLine);
            if (in_array($passedLine, $currentSteps)) {
                return array_search($passedLine, $currentSteps);
            }
            preg_match('/^\d+/', $passedLine, $matches);
            if (isset($matches[0])) {
                $passedLine = $matches[0];
            }
            if (!is_numeric($passedLine)) {
                throw new \RuntimeException('The line number must be a valid numeric value.');
            }

            return (int)$passedLine;
        });

        return $this->askQuestion($question);
    }

    public function askBeforeTestEnd(): int
    {
        $question = new Question(
            'The last test step was passed. Press the Enter to continue or Ctrl+C to exit: ',
        );
        $question->setAutocompleterValues([]);
        $question->setValidator(function () {
            return 1;
        });

        return $this->askQuestion($question);
    }

    private function askQuestion(Question $question): int
    {
        $question->setMaxAttempts(5);
        $output = new ConsoleOutput();
        try {
            $helper = new QuestionHelper();
            $inputLine = $helper->ask(new ArrayInput([]), $output, $question);
            if ($this->sessionHolder->isWatchFrom()) {
                $this->sessionHolder->setWatchFrom((int)$inputLine);
            }

            return (int)$inputLine;
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            throw new SkippTestExecutionException();
        }
    }

    private function getAutocompleteSteps(FeatureNode $featureNode, int $lastStepLine, bool $simple = false): array
    {
        $result = [];
        $actualFeature = $this->fileLoader->baseLoad($featureNode->getFile());
        if (!$actualFeature[0] instanceof FeatureNode) {
            return $result;
        }
        foreach ($actualFeature[0]->getScenarios() as $scenario) {
            foreach ($scenario->getSteps() as $step) {
                // all steps before failed
                if ($step->getLine() > $lastStepLine) {
                    return $result;
                }
                $result[$step->getLine()] = $step->getLine() . ' ' . $step->getKeyword() . ' ' . $step->getText();
            }
        }
        if ($simple) {
            return array_values($result);
        }

        return $result;
    }
}
