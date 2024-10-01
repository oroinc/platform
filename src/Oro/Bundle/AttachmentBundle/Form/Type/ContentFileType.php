<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\DataTransformer\ContentFileDataTransformerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType as ComponentFileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides form file type that saves content from the uploaded file but does not save file in the system
 */
class ContentFileType extends AbstractType
{
    public function __construct(
        private readonly ContentFileDataTransformerInterface $dataTransformer
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', ComponentFileType::class, [
                'constraints' => $options['fileConstraints']
            ])
            ->add('emptyFile', HiddenType::class, [
                'required' => false
            ]);

        if (!empty($options['fileName'])) {
            $this->dataTransformer->setFileName($options['fileName']);
        }

        $builder->addModelTransformer($this->dataTransformer);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'fileName' => null,
            'addEventSubscriber' => false,
            'fileConstraints' => [],
            'allowDelete' => true,
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return FileType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_content_file';
    }
}
