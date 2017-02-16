<?php

namespace Oro\Bundle\FormBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler\AutocompleteCompilerPass;
use Oro\Bundle\FormBundle\DependencyInjection\Compiler\FormCompilerPass;
use Oro\Bundle\FormBundle\DependencyInjection\Compiler\FormGuesserCompilerPass;
use Oro\Bundle\FormBundle\DependencyInjection\Compiler\FormHandlerCompilerPass;
use Oro\Bundle\FormBundle\DependencyInjection\Compiler\FormTemplateDataProviderCompilerPass;
use Oro\Bundle\FormBundle\Validator\HtmlPurifierTelValidator;

class OroFormBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AutocompleteCompilerPass());
        $container->addCompilerPass(new FormCompilerPass());
        $container->addCompilerPass(new FormGuesserCompilerPass());
        $container->addCompilerPass(new FormTemplateDataProviderCompilerPass());
        $container->addCompilerPass(new FormHandlerCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        \HTMLPurifier_URISchemeRegistry::instance()->register('tel', new HtmlPurifierTelValidator());
    }
}
