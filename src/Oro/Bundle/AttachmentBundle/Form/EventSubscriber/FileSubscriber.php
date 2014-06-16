<?php

namespace Oro\Bundle\AttachmentBundle\Form\EventSubscriber;

use Oro\Bundle\AttachmentBundle\Form\ImageType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\ConstraintViolation;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Symfony\Component\Validator\Validator;

class FileSubscriber implements EventSubscriberInterface
{
    protected $validator;

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::POST_SUBMIT => 'postSubmit'
        ];
    }

    /**
     * Add checkbox to delete attach file and delete owner select
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $entity = $event->getData();
        $form = $event->getForm();
        if (is_object($entity) && $entity->getId() && $entity->getFilename() !== null) {
            $form->add(
                'emptyFile',
                'checkbox',
                [
                    'label' => 'oro.attachment.delete_file.label',
                    'required'  => false,
                ]
            );
        }
        $form->remove('owner');
    }

    /**
     * Trigger attachment entity update
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var Attachment $entity */
        $entity = $event->getData();
        $form = $event->getForm();

        $fieldName = $form->getName();
        $dataClass = $form->getParent()->getConfig()->getDataClass();

        $fileField = $form->get('file');
        if (is_object($entity) && $entity->getFile() !== null) {
            $violations = $this->validator->validateValue(
                $entity->getFile(),
                [
                    new File()
                ]
            );

            if (!empty($violations)) {
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

        if (($form->has('emptyFile') && $form->get('emptyFile')->getData())
            || (is_object($entity) && $entity->getFile() !== null)
        ) {
            // trigger update in entity
            $entity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }
    }
}
