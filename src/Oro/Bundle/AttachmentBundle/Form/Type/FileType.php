<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType as SymfonyFileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form type for uploading file.
 */
class FileType extends AbstractType
{
    /** @var EventSubscriberInterface */
    private $eventSubscriber;

    public function setEventSubscriber(EventSubscriberInterface $eventSubscriber): void
    {
        $this->eventSubscriber = $eventSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', SymfonyFileType::class, $options['fileOptions']);

        // Adds emptyFile field if allowDelete option is true, removes owner field.
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);

        // Changes File::$updatedAt when new file is uploaded or file is marked for deletion.
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);

        if ($options['addEventSubscriber']) {
            $builder->addEventSubscriber($this->eventSubscriber);
        }
    }

    public function preSetData(FormEvent $event): void
    {
        $form = $event->getForm();

        $form->remove('owner');

        if ($form->getConfig()->getOption('allowDelete')) {
            $form->add('emptyFile', HiddenType::class, ['required' => false]);
        }
    }

    public function postSubmit(FormEvent $event): void
    {
        /** @var File $entity */
        $entity = $event->getData();
        $isEmptyFile = $entity && $entity->isEmptyFile();

        // Property File::$file is filled only when new file is uploaded.
        $isNewFile = $entity && $entity->getFile() !== null;

        if ($isNewFile || $isEmptyFile) {
            // Makes doctrine update File entity to enforce triggering of FileListener which uploads an image.
            $entity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => File::class,
                'checkEmptyFile' => false,
                'allowDelete' => true,
                'addEventSubscriber' => true,
                'fileOptions' => [],
            ]
        );

        $resolver->setAllowedTypes('fileOptions', 'array');
        $resolver->setNormalizer('fileOptions', \Closure::fromCallable([$this, 'normalizeFileOptions']));
    }

    public function normalizeFileOptions(Options $allOptions, array $option): array
    {
        if (!array_key_exists('required', $option)) {
            $option['required'] = $allOptions['checkEmptyFile'];
        }

        if (!array_key_exists('constraints', $option) && $allOptions['checkEmptyFile']) {
            $option['constraints'] = [new NotBlank()];
        }

        if (!array_key_exists('label', $option)) {
            $option['label'] = 'oro.attachment.file.label';
        }

        return $option;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_file';
    }
}
