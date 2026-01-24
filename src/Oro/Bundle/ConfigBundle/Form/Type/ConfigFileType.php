<?php

namespace Oro\Bundle\ConfigBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\ConfigBundle\Form\DataTransformer\ConfigFileDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType as ComponentFileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for handling file uploads in system configuration.
 *
 * Extends the attachment bundle's {@see FileType} to provide specialized file handling for
 * system configuration fields. This form type manages both file uploads and the ability
 * to clear/delete files through an "emptyFile" hidden field. It applies a data transformer
 * to convert between form data and configuration storage format, and supports configurable
 * file constraints for validation.
 */
class ConfigFileType extends AbstractType
{
    /**
     * @var ConfigFileDataTransformer
     */
    private $transformer;

    public function __construct(ConfigFileDataTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'file',
                ComponentFileType::class,
                [
                    'constraints' => $options['fileConstraints']
                ]
            )
            ->add(
                'emptyFile',
                HiddenType::class,
                [
                    'required' => false,
                ]
            );

        $this->transformer->setFileConstraints($builder->get('file')->getOption('constraints'));
        $builder->addModelTransformer($this->transformer);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'addEventSubscriber' => false,
            'fileConstraints' => [],
            'allowDelete' => true
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return FileType::class;
    }
}
