<?php

namespace Oro\Component\Action\Model;

use Oro\Component\Action\Event\ExtendableConditionEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExtendableConditionEventErrorsProcessor implements ExtendableConditionEventErrorsProcessorInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private RequestStack $requestStack
    ) {
    }

    public function getPreparedErrors(
        ExtendableConditionEvent $event,
        array|\ArrayAccess|null &$errorsCollection = null
    ): array {
        $errors = [];
        foreach ($event->getErrors() as $error) {
            $errors[] = $this->translator->trans($error['message']);
            if ($errorsCollection) {
                $errorsCollection[] = ['message' => $error['message'], 'parameters' => ($error['parameters'] ?? [])];
            }
        }

        return $errors;
    }

    public function showErrors(iterable $errors, string $messageType): void
    {
        foreach ($errors as $error) {
            $this->requestStack?->getSession()?->getFlashBag()->add($messageType, $error);
        }
    }

    public function processErrors(
        ExtendableConditionEvent $event,
        bool $showErrors = false,
        array|\ArrayAccess|null &$errorsCollection = null,
        string $messageType = 'error'
    ): array {
        if ($event->hasErrors()) {
            $eventErrors = $this->getPreparedErrors($event, $errorsCollection);
            if ($showErrors) {
                $this->showErrors($eventErrors, $messageType);
            }

            return $eventErrors;
        }

        return [];
    }
}
