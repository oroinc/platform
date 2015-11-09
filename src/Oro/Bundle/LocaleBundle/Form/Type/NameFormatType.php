<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;

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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
        return 'oro_name_format';
    }
}
