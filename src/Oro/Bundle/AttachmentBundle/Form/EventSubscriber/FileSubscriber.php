<?php

namespace Oro\Bundle\AttachmentBundle\Form\EventSubscriber;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Validates uploaded file via FileConfigValidator.
 */
class FileSubscriber implements EventSubscriberInterface
{
    /** @var ConfigFileValidator */
    protected $validator;

    public function __construct(ConfigFileValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT  => 'postSubmit'
        ];
    }

    /**
     * Trigger attachment entity update
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var File $entity */
        $entity = $event->getData();
        $form   = $event->getForm();

        if (is_object($entity) && $entity->getFile() !== null) {
            $this->validate($form, $entity);
        }
    }

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
            $dataClass = $form->getParent()->getConfig()->getDataClass();
            if (!$dataClass) {
                $dataClass = $form->getParent()->getParent()->getConfig()->getDataClass();
            }
        }

        if ($dataClass === FileItem::class) {
            $dataClass = $form->getParent()->getParent()->getParent()->getConfig()->getDataClass();
            $fieldName = (string)$form->getParent()->getParent()->getPropertyPath();
        }

        $violations = $this->validator->validate($entity->getFile(), $dataClass, $fieldName);

        if ($violations->count()) {
            $fileField = $form->get('file');
            /** @var $violation ConstraintViolation */
            foreach ($violations as $violation) {
                $error = new FormError(
                    $violation->getMessage(),
                    $violation->getMessageTemplate(),
                    $violation->getParameters()
                );
                $fileField->addError($error);
            }
        }
    }
}
