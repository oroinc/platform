<?php

declare(strict_types=1);

namespace Oro\Bundle\ThemeBundle\Form\Type;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Theme select type
 */
class ThemeSelectType extends AbstractType
{
    /**
     * @var Theme[]
     */
    protected array $themes = [];

    public function __construct(protected readonly ?ThemeManager $themeManager = null)
    {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->getChoices(),
        ]);
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
        foreach ($this->getThemes() as $theme) {
            $metadata[$theme->getName()] = [
                'icon' => $theme->getIcon(),
                'logo' => $theme->getLogo(),
                'screenshot' => $theme->getScreenshot(),
                'description' => $theme->getDescription()
            ];
        }
        $view->vars['themes-metadata'] = $metadata;
    }

    protected function getChoices(): array
    {
        $choices = [];

        foreach ($this->getThemes() as $theme) {
            $choices[$theme->getLabel()] = $theme->getName();
        }

        return $choices;
    }

    /**
     * @return Theme[]
     */
    protected function getThemes(): array
    {
        if (!$this->themes && $this->themeManager) {
            $this->themes = $this->themeManager->getEnabledThemes();
        }

        return $this->themes;
    }
}
