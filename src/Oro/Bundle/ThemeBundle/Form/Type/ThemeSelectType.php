<?php

declare(strict_types=1);

namespace Oro\Bundle\ThemeBundle\Form\Type;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Theme select type for Platform
 */
class ThemeSelectType extends AbstractType
{
    /**
     * @var array<string, Theme[]>
     */
    private array $themes = [];

    public function __construct(private readonly ThemeManager $themeManager)
    {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'theme_group' => null,
            'choices' => function (Options $options) {
                return $this->getChoices($options['theme_group']);
            },
        ]);

        $resolver->setAllowedTypes('theme_group', ['null', 'string', 'array']);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_frontend_theme_select';
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $metadata = [];
        foreach ($this->getThemes($options['theme_group']) as $theme) {
            $metadata[$theme->getName()] = [
                'icon' => $theme->getIcon(),
                'logo' => $theme->getLogo(),
                'screenshot' => $theme->getScreenshot(),
                'description' => $theme->getDescription()
            ];
        }
        $view->vars['themes-metadata'] = $metadata;
    }

    /**
     * @param string|array|null $group Theme group to filter by
     * @return array
     */
    private function getChoices(string|array|null $group = null): array
    {
        $choices = [];

        foreach ($this->getThemes($group) as $theme) {
            $choices[$theme->getLabel()] = $theme->getName();
        }

        return $choices;
    }

    /**
     * @param string|array|null $group Theme group to filter by
     * @return Theme[]
     */
    private function getThemes(string|array|null $group = null): array
    {
        $cacheKey = $group === null ? 'all' : (is_array($group) ? implode(',', $group) : $group);

        if (!isset($this->themes[$cacheKey])) {
            $this->themes[$cacheKey] = $this->themeManager->getEnabledThemes($group);
        }

        return $this->themes[$cacheKey];
    }
}
