<?php

namespace Oro\Bundle\WorkflowBundle\Form\EventSubscriber;

use Symfony\Component\Form\FormError;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;

use Oro\Bundle\AttachmentBundle\Form\EventSubscriber\FileSubscriber as BaseFileSubscriber;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class FileSubscriber extends BaseFileSubscriber
{


    /**
     * Validate attachment field
     *
     * @param FormInterface   $form
     * @param File|Attachment $entity
     */
    protected function validate(FormInterface $form, $entity)
    {
        $fieldName = $form->getName();

        if ($form->getParent()->getConfig()->getOption('parentEntityClass', null)) {
            $dataClass = $form->getParent()->getConfig()->getOption('parentEntityClass', null);
            $fieldName = '';
        } else {
            $dataClass = $form->getParent()
                ? $form->getParent()->getConfig()->getDataClass()
                : $form->getConfig()->getDataClass();
            if (!$dataClass) {
                $dataClass = $form->getParent()->getParent()->getConfig()->getDataClass();
            }
        }

        if ($dataClass == 'Oro\Bundle\WorkflowBundle\Model\WorkflowData') {
            $data = $this->getRealWorkflowData($form);
            $dataClass = $data['dataClass'];
            $fieldName = $data['fieldName'];
        }

        $violations = $this->validator->validate($entity->getFile(), $dataClass, $fieldName);

        if (!empty($violations)) {
            $fileField = $form->get('file');
            /** @var $violation ConstraintViolation */
            foreach ($violations as $violation) {
                $error = new FormError(
                    $violation->getMessage(),
                    $violation->getMessageTemplate(),
                    $violation->getMessageParameters()
                );
                $fileField->addError($error);
            }
        }
    }

    /**
     * @param FormInterface $form
     * @return array
     * @throws \Exception
     */
    protected function getRealWorkflowData(FormInterface $form)
    {
        $options = $options = $form->getRoot()->getConfig()->getOptions();

        if (empty($options['workflow'])) {
            throw new \Exception('The object not found');
        }

        /** @var Workflow $workflow */
        $workflow = $options['workflow'];

        $attributeManager = $workflow->getAttributeManager();

        $fieldAttribute = $attributeManager->getAttribute($form->getName());

        if (!$fieldAttribute) {
            throw new \Ecxeption('Field attribute not found');
        }

        $propertyPath = $fieldAttribute->getPropertyPath();

        $propertyPathData = explode('.', $propertyPath);

        $fieldName = array_pop($propertyPathData);
        $dataClassAlias = array_pop($propertyPathData);

        $dataClassAttribute = $attributeManager->getAttribute($dataClassAlias);

        $dataClass = $dataClassAttribute->getOption('class');

        if (!$dataClass) {
           throw new \Exception('Data class not found');
        }

        return [
            'fieldName' => $fieldName,
            'dataClass'  => $dataClass
        ];
    }
}
