<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Provide supporting 'checkbox' form type for the theme configuration section of theme.yml files
 */
class CheckboxBuilder extends AbstractConfigurationChildBuilder
{
    #[\Override] public static function getType(): string
    {
        return 'checkbox';
    }

    #[\Override]
    public function buildOption(FormBuilderInterface $builder, array $option): void
    {
        parent::buildOption($builder, $option);

        $builder
            ->get($option['name'])
            ->addModelTransformer(new CallbackTransformer(
                function ($value) {
                    return match ($value) {
                        'checked' => true,
                        'unchecked' => false,
                        default => $value
                    };
                },
                function ($value) {
                    return $value;
                }
            ));
    }

    #[\Override]
    protected function getTypeClass(): string
    {
        return CheckboxType::class;
    }

    #[\Override]
    protected function getDefaultOptions(): array
    {
        return [
            'required' => false,
            'false_values' => ['unchecked', null],
        ];
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $formOptions, array $themeOption): void
    {
        parent::finishView($view, $form, $formOptions, $themeOption);

        foreach ($themeOption['previews'] ?? [] as $value => $preview) {
            if ($value === static::DEFAULT_PREVIEW_KEY) {
                continue;
            }

            $view->vars['attr']["data-preview-$value"] = $this->getOptionPreview($themeOption, $value);
        }
    }

    #[\Override]
    protected function getOptionPreview(array $option, mixed $value = null, bool $default = false): ?string
    {
        $value = match ($value) {
            true => 'checked',
            false => 'unchecked',
            default => $value
        };

        return parent::getOptionPreview($option, $value, $default);
    }
}
