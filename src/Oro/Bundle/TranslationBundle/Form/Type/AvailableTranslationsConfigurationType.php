<?php

namespace Oro\Bundle\TranslationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\ObjectToJsonTransformer;

class AvailableTranslationsConfigurationType extends AbstractType
{
    const NAME = 'oro_translation_available_translations';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new ObjectToJsonTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
