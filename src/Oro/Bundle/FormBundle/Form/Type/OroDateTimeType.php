<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\DateTimeExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OroDateTimeType extends OroDateType
{
    const NAME = 'oro_datetime';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(['format' => DateTimeExtension::HTML5_FORMAT_WITH_TIMEZONE]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'datetime';
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
}
