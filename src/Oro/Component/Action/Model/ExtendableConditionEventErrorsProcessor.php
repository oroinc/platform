<?php

namespace Oro\Component\Action\Model;

use Oro\Component\Action\Event\ExtendableConditionEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Processes extendable condition errors.
 */
class ExtendableConditionEventErrorsProcessor implements ExtendableConditionEventErrorsProcessorInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private RequestStack $requestStack
    ) {
    }

    public function processErrors(
        ExtendableConditionEvent $event,
        bool $showErrors = false,
        array|\ArrayAccess|null &$errorsCollection = null,
        string $messageType = 'error'
    ): array {
        if (!$event->hasErrors()) {
            return [];
        }

        $eventErrors = $this->getPreparedErrors($event, $showErrors, $errorsCollection);
        if ($showErrors) {
            $this->showErrors($eventErrors, $messageType);
        }

        return $eventErrors;
    }

    private function getPreparedErrors(
        ExtendableConditionEvent $event,
        bool $showErrors = false,
        array|\ArrayAccess|null &$errorsCollection = null
    ): array {
        $errors = [];
        foreach ($event->getErrors() as $error) {
            [$message, $rawMessage, $rawMessageParams] = $this->getMessageDetails($error);

            $errors[] = $message;
            if (!$showErrors && $errorsCollection !== null) {
                $errorsCollection[] = ['message' => $rawMessage, 'parameters' => $rawMessageParams];
            }
        }

        return $errors;
    }

    private function showErrors(iterable $errors, string $messageType): void
    {
        $flashBag = $this->requestStack?->getSession()?->getFlashBag();
        if (!$flashBag) {
            return;
        }

        foreach ($errors as $error) {
            $flashBag->add($messageType, $error);
        }
    }

    private function getMessageDetails(array $error): array
    {
        $context = $error['context'] ?? null;
        if ($context instanceof ConstraintViolationInterface) {
            return [
                $context->getMessage(),
                $context->getMessageTemplate() ?? $context->getMessage(),
                $context->getParameters() ?? []
            ];
        }

        return [
            $this->translator->trans($error['message']),
            $error['message'],
            []
        ];
    }
}
