<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\FileType as SymfonyFileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Represents a form type for {@see File}.
 */
class FileType extends AbstractType
{
    private ExternalFileFactory $externalFileFactory;

    private ?EventSubscriberInterface $eventSubscriber = null;

    public function __construct(ExternalFileFactory $externalFileFactory)
    {
        $this->externalFileFactory = $externalFileFactory;
    }

    public function setEventSubscriber(EventSubscriberInterface $eventSubscriber): void
    {
        $this->eventSubscriber = $eventSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['isExternalFile']) {
            $builder->add(
                $builder
                    ->create('file', ExternalFileType::class, $options['fileOptions'])
                    ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'filePreSetData'])
            );
        } else {
            $builder->add('file', SymfonyFileType::class, $options['fileOptions']);
        }

        $builder->add('emptyFile', HiddenType::class);

        // Removes owner field.
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);

        if ($options['addEventSubscriber']) {
            $builder->addEventSubscriber($this->eventSubscriber);
        }
    }

    public function filePreSetData(PreSetDataEvent $event): void
    {
        /** @var File|null $file */
        $file = $event->getForm()->getParent()?->getData();
        if ($file) {
            $event->setData($this->externalFileFactory->createFromFile($file));
        }
    }

    public function preSetData(PreSetDataEvent $event): void
    {
        $event->getForm()->remove('owner');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['allowDelete'] = $options['allowDelete'];
        $view->vars['attachmentViewOptions']['isExternalFile'] = $options['isExternalFile'];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attachmentViewOptions']['fileSelector'] = '#' . $view['file']->vars['id'];
        $view->vars['attachmentViewOptions']['emptyFileSelector'] = '#' . $view['emptyFile']->vars['id'];

        $view->vars['label_attr']['for'] = $view['file']->vars['id'];
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
                'isExternalFile' => false,
            ]
        );

        $resolver->setAllowedTypes('isExternalFile', 'bool');
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
