<?php

namespace Oro\Bundle\FormBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all CAPTCHA protected forms.
 */
class CaptchaProtectedFormsCompilerPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    private const string TAG_NAME = 'oro_form.captcha_protected';

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $protectedForms = [];
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $tags) {
            foreach ($tags as $attributes) {
                $protectedForms[] = $this->getRequiredAttribute($attributes, 'form_name', $id, self::TAG_NAME);
            }
        }
        $container->getDefinition('oro_form.captcha.protected_forms_registry')
            ->setArgument('$protectedForms', array_unique($protectedForms));
    }
}
