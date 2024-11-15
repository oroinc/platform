<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatOpenAIExtension\Healer\Extension;

use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\Call\Call;
use Behat\Testwork\Call\CallResult;
use Behat\Testwork\Output\Formatter;
use OpenAI\Client;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;
use Oro\Bundle\TestFrameworkBundle\Behat\Healer\HealerInterface;
use voku\helper\HtmlMin;

/**
 * Running OpenAI to heal failed clickable steps.
 */
class OpenAIClickableStepHealer implements HealerInterface
{
    protected const string OPEN_AI_PROJECT = 'gpt-3.5-turbo-0125';
    protected const string PROMPT_MASK = "As a test automation engineer I am testing web application using Behat.
        I want to heal a test that fails.
        Propose how to adjust `%s` failed step to fix the test.
        In the answer, give only the line of the proposed step correction.
        Use locators in order of preference: semantic locator by label, id, text.
        Here is the error message: '%s'
        Here is clickable HTML elements of a page where the failure has happened: \n\n'%s',
   ";

    protected const array SUPPORTED_STEPS = [
        'pressButton'
    ];

    public function __construct(
        protected OroElementFactory $elementFactory,
        protected Formatter $formatter,
        protected Client $client,
    ) {
    }

    public function supports(Call $call): bool
    {
        if (!isset($call->getBoundCallable()[1])) {
            return false;
        }
        if (isset($call->getArguments()['button'])
            && in_array($call->getBoundCallable()[1], self::SUPPORTED_STEPS, true)
        ) {
            return true;
        }

        return false;
    }

    public function process(Call &$call, CallResult $failedCall): bool
    {
        try {
            $clickableElements = $this->findAllClickableElements();
            if (empty($clickableElements)) {
                return false;
            }
            $parameters = $this->prepareOpenAIParams($call, $failedCall, $clickableElements);
            $response = $this->client->chat()->create($parameters);
            $failedStep = $call->getStep();

            if (empty($response->choices[0]->message->content)
                || !str_starts_with(trim($response->choices[0]->message->content, '`'), $failedStep->getKeyword())
            ) {
                return false;
            }
            $newStepText = substr(
                trim($response->choices[0]->message->content, '`'),
                strlen($failedStep->getKeyword())
            );
            $newStep = $this->prepareProposedStep($failedStep, $newStepText);
            // override failed step with proposed
            $call = new $call(
                $call->getEnvironment(),
                $call->getFeature(),
                $newStep,
                $call->getCallee(),
                $newStep->getArguments(),
                $call->getErrorReportingLevel()
            );
        } catch (\Throwable $exception) {
            // failed attempt to fix the step
            return false;
        }
        $this->writeOutput(
            sprintf(
                'Suggested changes: FROM: `%s`, TO: `%s`',
                $failedStep->getText(),
                $newStep->getText()
            )
        );

        return true;
    }

    protected function findAllClickableElements(): array
    {
        $session = $this->elementFactory->getPage()->getSession();
        // find clickable html elements
        $script = "return Array.from(
            document.querySelectorAll(
                'a, button, input[type=\"button\"], input[type=\"submit\"], area, [tabindex]:not([tabindex=\"-1\"])')
            )
            .filter(el => (el.innerText && el.innerText.trim() !== '') || (el.value && el.value.trim() !== ''))
            .map(el => el.outerHTML);";

        $result = $session->evaluateScript($script);

        return is_array($result) ? $result : [];
    }

    protected function prepareProposedStep(StepNode $failedStep, string $newStepText): StepNode
    {
        $newStepArgs = $this->getArgumentsFromStep($newStepText);

        return new StepNode(
            $failedStep->getKeyword(),
            trim($newStepText),
            $newStepArgs,
            $failedStep->getLine(),
            $failedStep->getKeywordType()
        );
    }

    protected function prepareOpenAIParams(Call $call, CallResult $failedCall, array $clickableElements): array
    {
        $failedStep = $call->getStep()->getKeyword() . ' ' . $call->getStep()->getText();
        $errorMessage = $failedCall->getException()->getMessage();
        $html = (new HtmlMin())->minify(implode("\n", $clickableElements));

        return [
            'model' => self::OPEN_AI_PROJECT,
            'temperature' => 0,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => sprintf(self::PROMPT_MASK, $failedStep, $errorMessage, $html),
                ],
            ],
        ];
    }

    protected function getArgumentsFromStep(string $step): array
    {
        $matches = [];
        preg_match_all('/"(.*?)"/', trim($step), $matches);

        return $matches[1] ?? [];
    }

    protected function writeOutput(string $text, string $style = 'pending'): void
    {
        $this->formatter->getOutputPrinter()->writeln(
            sprintf(
                '%s{+%s}%s{-%s}',
                '       ',
                $style,
                $text,
                $style
            )
        );
    }

    public function getLabel(): string
    {
        return 'Running OpenAI healer for clickable steps';
    }

    public function fallInAnyResult(): bool
    {
        return true;
    }
}
