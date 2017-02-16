<?php

namespace Oro\Bundle\FormBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FormHandlerCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const REGISTRY_SERVICE = 'oro_form.registry.form_handler';
    const PROVIDER_TAG = 'oro_form.form.handler';

    /** {@inheritdoc} */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            self::REGISTRY_SERVICE,
            self::PROVIDER_TAG,
            'addHandler'
        );
    }
}
