<?php

namespace Oro\Bundle\AttachmentBundle\Form\EventSubscriber;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;

class FileSubscriber implements EventSubscriberInterface
{
    /** @var ConfigFileValidator */
    protected $validator;

    /**
     * @param ConfigFileValidator $validator
     */
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
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::POST_SUBMIT  => 'postSubmit'
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
        $form   = $event->getForm();

        if (is_object($entity)
            && $entity->getId()
            && $entity->getFilename() !== null
            && $form->getConfig()->getOption(
                'allowDelete'
            )
        ) {
            $form->add(
                'emptyFile',
                HiddenType::class,
                [
                    'required' => false,
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
        /** @var File $entity */
        $entity = $event->getData();
        $form   = $event->getForm();

        if (is_object($entity) && $entity->getFile() !== null) {
            $this->validate($form, $entity);
        }

        if (($form->has('emptyFile') && $form->get('emptyFile')->getData())
            || (is_object($entity) && $entity->getFile() !== null)
        ) {
            // trigger update in entity
            $entity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
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
            $dataClass = $form->getParent()
                ? $form->getParent()->getConfig()->getDataClass()
                : $form->getConfig()->getDataClass();
            if (!$dataClass) {
                $dataClass = $form->getParent()->getParent()->getConfig()->getDataClass();
            }
        }

        $violations = $this->validator->validate($entity->getFile(), $dataClass, $fieldName);

        if (!empty($violations)) {
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
