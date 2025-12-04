<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\Form\FormTypeCsrfExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Sets up FormTypeCsrfExtension
 */
class FormTypeCsrfPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('form.type_extension.csrf')
            ->setClass(FormTypeCsrfExtension::class)
            ->addArgument(['data-controller' => 'csrf-protection']) # data-controller: csrf-protection
            ->addArgument('submit'); # token_id: submit
    }
}
