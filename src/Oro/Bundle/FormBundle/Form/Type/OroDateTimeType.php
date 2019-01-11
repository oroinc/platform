<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\DateTimeExtension;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OroDateTimeType extends OroDateType
{
    const NAME = 'oro_datetime';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        /**
         * Fix BC break was made in Symfony v2.8.46.
         * @link https://github.com/symfony/symfony/pull/28466
         * @link https://github.com/symfony/symfony/issues/28703
         */
        $builder->addViewTransformer(new CallbackTransformer(
            function ($value) {
                return $value;
            },
            function ($value) {
                if (is_string($value) && $value) {
                    $timestamp = strtotime($value);
                    if (false !== $timestamp) {
                        $value = date('Y-m-d\\TH:i:sP', $timestamp);
                    }
                }

                return $value;
            }
        ));
    }

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
