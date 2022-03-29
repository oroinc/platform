<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Image collection
 */
class MultiImageType extends AbstractType
{
    const TYPE = 'oro_attachment_multi_image';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->addNormalizer('entry_options', static function (Options $allOptions, array $option) {
            if (!isset($option['file_type'])) {
                $option['file_type'] = ImageType::class;
            }

            return $option;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return MultiFileType::class;
    }
}
