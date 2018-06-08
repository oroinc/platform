<?php

namespace Oro\Bundle\ApiBundle\Form\EventListener;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * This listener is intended to
 * * reset value of a field bound to CompoundObjectType form type
 * * add mandatory value constraint violation for fields with "required" option is set to TRUE
 *   and that value does not exist in the submitted data
 */
class CompoundObjectListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit'
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $submittedData = $event->getData();
        if (null === $submittedData) {
            $submittedData = [];
            foreach ($form as $name => $child) {
                $submittedData[$name] = null;
                if ($child->isRequired()) {
                    $this->addRequiredFieldConstraintViolation($form, $name);
                }
            }
            $event->setData($submittedData);
        } elseif (\is_array($submittedData)) {
            /** @var FormInterface $child */
            foreach ($form as $name => $child) {
                if (!\array_key_exists($name, $submittedData) && $child->isRequired()) {
                    $this->addRequiredFieldConstraintViolation($form, $name);
                }
            }
        }
    }

    /**
     * @param FormInterface $form
     * @param string        $fieldName
     */
    private function addRequiredFieldConstraintViolation(FormInterface $form, string $fieldName): void
    {
        FormUtil::addFormError($form, 'This value is mandatory.', $fieldName);
    }
}
