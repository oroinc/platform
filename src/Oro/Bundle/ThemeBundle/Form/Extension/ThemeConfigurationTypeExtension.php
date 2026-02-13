<?php

declare(strict_types=1);

namespace Oro\Bundle\ThemeBundle\Form\Extension;

use Oro\Bundle\ThemeBundle\Form\Type\ThemeConfigurationType;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationTypeProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Adds a type field to ThemeConfigurationType
 */
class ThemeConfigurationTypeExtension extends AbstractTypeExtension
{
    public function __construct(
        private ThemeConfigurationTypeProvider $themeConfigurationTypeProvider
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'oro.theme.themeconfiguration.type.label',
                'required' => true,
                'choices' => $this->themeConfigurationTypeProvider->getLabelsAndTypes()
            ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ThemeConfigurationType::class];
    }
}
