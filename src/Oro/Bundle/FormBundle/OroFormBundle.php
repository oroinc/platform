<?php

namespace Oro\Bundle\FormBundle;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler;
use Oro\Bundle\FormBundle\Validator\HtmlPurifierTelValidator;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroFormBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        parent::boot();

        \HTMLPurifier_URISchemeRegistry::instance()->register('tel', new HtmlPurifierTelValidator());
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\AutocompleteCompilerPass());
        $container->addCompilerPass(new Compiler\FormGuesserCompilerPass());
        $container->addCompilerPass(new PriorityTaggedLocatorCompilerPass(
            'oro_form.registry.form_template_data_provider',
            'oro_form.form_template_data_provider',
            'alias'
        ));
        $container->addCompilerPass(new PriorityTaggedLocatorCompilerPass(
            'oro_form.registry.form_handler',
            'oro_form.form.handler',
            'alias'
        ));
    }
}
