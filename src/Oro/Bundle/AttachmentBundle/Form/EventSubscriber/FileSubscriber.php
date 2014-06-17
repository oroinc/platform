<?php

namespace Oro\Bundle\AttachmentBundle\Form\EventSubscriber;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\ConfigBundle\Config\UserConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class FileSubscriber implements EventSubscriberInterface
{
    /** @var Validator  */
    protected $validator;

    /** @var UserConfigManager */
    protected $config;

    /** @var ConfigProvider */
    protected $attachmentConfigProvider;

    public function __construct(Validator $validator, ConfigManager $configManager, UserConfigManager $config)
    {
        $this->validator = $validator;
        $this->attachmentConfigProvider = $configManager->getProvider('attachment');
        $this->config = $config;
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
     * @param FormInterface $form
     * @param Attachment    $entity
     */
    protected function validate(FormInterface $form, Attachment $entity)
    {
        $fieldName = $form->getName();
        $dataClass = $form->getParent()->getParent()->getConfig()->getDataClass();

        /** @var Config $entityExtendConfig */
        $entityExtendConfig = $this->attachmentConfigProvider->getConfig($dataClass, $fieldName);

        $fileSize = $entityExtendConfig->get('maxsize') * 1024 *1024;
        $fileField = $form->get('file');

        if ($entityExtendConfig->getId()->getFieldType() === 'attachment') {
            $configValue = 'upload_mime_types';
        } else {
            $configValue = 'upload_image_mime_types';
        }

        $mimeTypes = explode("\n", $this->config->get('oro_attachment.' . $configValue));
        foreach ($mimeTypes as $id => $value) {
            $mimeTypes[$id] = trim($value);
        }

        $violations = $this->validator->validateValue(
            $entity->getFile(),
            [
                new File(
                    [
                        'maxSize' => $fileSize,
                        'mimeTypes' => $mimeTypes
                    ]
                )
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
}
