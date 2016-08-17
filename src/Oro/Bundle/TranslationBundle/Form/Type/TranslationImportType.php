<?php

namespace Oro\Bundle\TranslationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

use Oro\Bundle\ImportExportBundle\Form\Type\ImportType;

class TranslationImportType extends AbstractType
{
    const NAME = 'oro_translation_import';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'file',
            'file',
            [
                'required' => true,
                'constraints' => [
                    new File(
                        [
                            'mimeTypes' => ['application/zip'],
                            'mimeTypesMessage' => 'This file type is not allowed.'
                        ]
                    )
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ImportType::NAME;
    }
}
