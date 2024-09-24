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

    public function __construct(NameFormatter $nameFormatter)
    {
        $this->nameFormatter = $nameFormatter;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data' => $this->nameFormatter->getNameFormat()
            )
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_name_format';
    }
}
