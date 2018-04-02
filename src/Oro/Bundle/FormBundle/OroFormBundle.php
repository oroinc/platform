<?php

namespace Oro\Bundle\FormBundle;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler\AutocompleteCompilerPass;
use Oro\Bundle\FormBundle\DependencyInjection\Compiler\FormGuesserCompilerPass;
use Oro\Bundle\FormBundle\Validator\HtmlPurifierTelValidator;
use Oro\Component\DependencyInjection\Compiler\TaggedServiceLinkRegistryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroFormBundle extends Bundle
{
    const FORM_TEMPLATE_DATA_PROVIDER_TAG = 'oro_form.form_template_data_provider';
    const FORM_HANDLER_TAG = 'oro_form.form.handler';

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AutocompleteCompilerPass());
        $container->addCompilerPass(new FormGuesserCompilerPass());
        $container->addCompilerPass(
            new TaggedServiceLinkRegistryCompilerPass(
                self::FORM_TEMPLATE_DATA_PROVIDER_TAG,
                'oro_form.registry.form_template_data_provider'
            )
        );
        $container->addCompilerPass(
            new TaggedServiceLinkRegistryCompilerPass(
                self::FORM_HANDLER_TAG,
                'oro_form.registry.form_handler'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        \HTMLPurifier_URISchemeRegistry::instance()->register('tel', new HtmlPurifierTelValidator());
    }
}
