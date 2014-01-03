<?php

namespace Oro\Bundle\TranslationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\ObjectToJsonTransformer;

class AvailableTranslationsConfigurationType extends AbstractType
{
    const NAME = 'oro_translation_available_translations';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new ObjectToJsonTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['data_class' => '\stdClass']);
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
