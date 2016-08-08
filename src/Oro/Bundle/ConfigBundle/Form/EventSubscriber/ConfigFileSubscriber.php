<?php

namespace Oro\Bundle\ConfigBundle\Form\EventSubscriber;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ConfigFileSubscriber implements EventSubscriberInterface
{
    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::SUBMIT => 'submit'
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
                'hidden',
                [
                    'required' => false,
                ]
            );
        }
    }

    /**
     * @param FormEvent $event
     */
    public function submit(FormEvent $event)
    {
        /** @var File $entity */
        $entity = $event->getData();
        $form   = $event->getForm();
        /** @var UploadedFile $file */
        $file = $entity->getFile();

        if (($form->has('emptyFile') && $form->get('emptyFile')->getData())
            || (is_object($entity) && $file !== null)
        ) {
            $entity->setFilename($file->getClientOriginalName());
            $this->fileManager->upload($entity);

        }
    }
}
