<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Provide supporting 'checkbox' form type for the theme configuration section of theme.yml files
 */
class CheckboxBuilder extends AbstractConfigurationChildBuilder
{
    #[\Override] public static function getType(): string
    {
        return 'checkbox';
    }

    public function supports(array $option): bool
    {
        return $option['type'] === self::getType();
    }

    /**
     * {@inheritDoc}
     */
    public function buildOption(FormBuilderInterface $builder, array $option): void
    {
        parent::buildOption($builder, $option);

        $builder
            ->get($option['name'])
            ->addEventListener(
                FormEvents::PRE_SUBMIT,
                function (FormEvent $event) {
                    $data = $event->getData();
                    if (!$data) {
                        $event->setData('unchecked');
                    }
                }
            )
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

    /**
     * {@inheritDoc}
     */
    protected function getTypeClass(): string
    {
        return CheckboxType::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultOptions(): array
    {
        return [
            'required' => false,
            'false_values' => ['unchecked']
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getConfiguredOptions($option): array
    {
        return array_merge(parent::getConfiguredOptions($option), [
            'data' => $option['default'],
        ]);
    }
}
