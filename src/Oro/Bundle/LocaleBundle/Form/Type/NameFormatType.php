<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for editing name format patterns.
 *
 * This form type provides a text input field for configuring the name format
 * pattern used throughout the application. It extends {@see TextType} and automatically
 * populates the field with the current name format from the {@see NameFormatter} service,
 * allowing administrators to customize how names are displayed and formatted.
 */
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
