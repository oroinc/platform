<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Form\EventListener;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Disables and clears fields specified in "disable_fields_if" option.
 * The option must be defined (with allowed type "array") in a form type using this event listener. The array structure
 * of the option is expected to be as following:
 *  [
 *      // form field name => a condition written in Symfony expression language. Must return true to disable a field.
 *      'fieldName' => 'data["type"] == "some value"'
 *  ]
 */
class DisableFieldsEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ExpressionLanguage $expressionLanguage,
        private readonly PropertyAccessorInterface $propertyAccessor
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
            FormEvents::POST_SUBMIT => 'onPostSubmit',
        ];
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        foreach ($form->getConfig()->getOption('disable_fields_if') as $fieldName => $condition) {
            if (!$form->has($fieldName)) {
                continue;
            }

            $result = $this->expressionLanguage->evaluate($condition, ['form' => $form, 'data' => $event->getData()]);
            if ($result === true) {
                FormUtils::replaceField($form, $fieldName, ['disabled' => true]);
            }
        }
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();
        foreach ($form->getConfig()->getOption('disable_fields_if') as $fieldName => $condition) {
            if (!$form->has($fieldName)) {
                continue;
            }

            $result = $this->expressionLanguage->evaluate($condition, ['form' => $form, 'data' => $data]);
            if ($result === true) {
                $this->propertyAccessor->setValue($data, $fieldName, null);
            }
        }
    }
}
