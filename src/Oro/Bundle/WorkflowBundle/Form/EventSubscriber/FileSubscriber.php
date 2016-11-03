<?php

namespace Oro\Bundle\WorkflowBundle\Form\EventSubscriber;

use Symfony\Component\Form\FormError;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Form\EventSubscriber\FileSubscriber as BaseFileSubscriber;
use Oro\Bundle\WorkflowBundle\Utils\WorkflowHelper;

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

        if ($dataClass === 'Oro\Bundle\WorkflowBundle\Model\WorkflowData') {
            $data = WorkflowHelper::getRealWorkflowData($form);
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
}
