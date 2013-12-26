<?php
namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class TwigConfigurationPass implements CompilerPassInterface
{
    const TWIG_SERVICE_KEY = 'twig';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::TWIG_SERVICE_KEY)) {
            return;
        }

        $twig = $container->getDefinition(self::TWIG_SERVICE_KEY);
        $twig->addMethodCall(
            'addGlobal',
            [
                'show_pin_button_on_start_page',
                $container->getParameter('oro_ui.show_pin_button_on_start_page')
            ]
        );
    }
}
