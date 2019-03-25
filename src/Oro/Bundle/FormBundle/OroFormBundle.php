<?php

namespace Oro\Bundle\FormBundle;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler;
use Oro\Bundle\FormBundle\Validator\HtmlPurifierTelValidator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The FormBundle bundle class.
 */
class OroFormBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\AutocompleteCompilerPass());
        $container->addCompilerPass(new Compiler\FormGuesserCompilerPass());
        $container->addCompilerPass(new Compiler\FormTemplateDataProviderCompilerPass());
        $container->addCompilerPass(new Compiler\FormHandlerCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        \HTMLPurifier_URISchemeRegistry::instance()->register('tel', new HtmlPurifierTelValidator());
    }
}
