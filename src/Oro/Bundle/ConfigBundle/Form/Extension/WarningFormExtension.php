<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds support for an additional warning message for configuration form fields.
 */
class WarningFormExtension extends AbstractTypeExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined([
            'warning'
        ]);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (isset($options['warning'])) {
            $view->vars['warning'] = $options['warning'];
        }
    }
}
