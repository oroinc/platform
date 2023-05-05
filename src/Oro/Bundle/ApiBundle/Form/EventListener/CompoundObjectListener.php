<?php

namespace Oro\Bundle\ApiBundle\Form\EventListener;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * This listener is intended to
 * * reset value of a field bound to CompoundObjectType form type
 * * add mandatory value constraint violation for fields with "required" option is set to TRUE
 *   and that value does not exist in the submitted data
 * * add an entity processed by CompoundObjectType form type to the list of additional entities
 *   of API context within this form is processed
 */
class CompoundObjectListener implements EventSubscriberInterface
{
    private bool $setDataToNull = false;

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT  => 'preSubmit',
            FormEvents::SUBMIT      => 'onSubmit',
            FormEvents::POST_SUBMIT => ['postSubmit', -250]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function preSubmit(FormEvent $event): void
    {
        $this->setDataToNull = false;
        $form = $event->getForm();
        $submittedData = $event->getData();
        if (null === $submittedData) {
            if ($form->getConfig()->getRequired()) {
                $event->setData($this->getEmptySubmittedData($form));
            } else {
                $this->setDataToNull = true;
                foreach ($form->all() as $child) {
                    $form->remove($child->getName());
                }
            }
        } elseif (\is_array($submittedData)) {
            if ($submittedData) {
                $hasChanges = false;
                /** @var EntityMetadata $metadata */
                $metadata = $form->getConfig()->getOption('metadata');
                /** @var FormInterface $child */
                foreach ($form as $name => $child) {
                    if (\array_key_exists($name, $submittedData)) {
                        if ($this->isEmptyValue($submittedData, $name, $metadata)) {
                            $submittedData[$name] = null;
                            $hasChanges = true;
                        }
                    } elseif ($child->isRequired()) {
                        $this->addRequiredFieldConstraintViolation($form, $name);
                    }
                }
                if ($hasChanges) {
                    $event->setData($submittedData);
                }
            } elseif (null === $form->getData() && $form->getConfig()->getRequired()) {
                $event->setData($this->getEmptySubmittedData($form));
            }
        }
    }

    public function onSubmit(FormEvent $event): void
    {
        if ($this->setDataToNull) {
            $event->setData(null);
        }
    }

    public function postSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($form->getConfig()->getInheritData()) {
            return;
        }

        $entity = $form->getData();
        if (null === $entity) {
            return;
        }

        FormUtil::getApiContext($form)?->addAdditionalEntity($entity);
    }

    private function getEmptySubmittedData(FormInterface $form): array
    {
        $submittedData = [];
        foreach ($form as $name => $child) {
            $submittedData[$name] = null;
            if ($child->isRequired()) {
                $this->addRequiredFieldConstraintViolation($form, $name);
            }
        }

        return $submittedData;
    }

    private function addRequiredFieldConstraintViolation(FormInterface $form, string $fieldName): void
    {
        FormUtil::addFormError($form, 'This value is mandatory.', $fieldName);
    }

    private function isEmptyValue(array $submittedData, string $name, EntityMetadata $metadata): bool
    {
        $dataType = $metadata->getProperty($name)?->getDataType();
        if (null === $dataType || DataType::STRING === $dataType) {
            return '' === $submittedData[$name];
        }
        if (DataType::OBJECT === $dataType || DataType::isArray($dataType)) {
            return [] === $submittedData[$name];
        }

        return false;
    }
}
