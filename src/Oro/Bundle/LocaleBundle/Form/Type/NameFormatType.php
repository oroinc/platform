<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NameFormatType extends AbstractType
{
    /**
     * @var NameFormatter
     */
    protected $nameFormatter;

    /**
     * @param NameFormatter $nameFormatter
     */
    public function __construct(NameFormatter $nameFormatter)
    {
        $this->nameFormatter = $nameFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data' => $this->nameFormatter->getNameFormat()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
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
        return 'oro_name_format';
    }
}
