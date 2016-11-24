<?php

namespace Oro\Bundle\LoggerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

/**
 * This compiler pass hides detailed logs handler's nested handler for all channels
 * and also injects configured nested handler into detailed logs handler
 */
class SwiftMailerHandlerPass implements CompilerPassInterface
{
    const SWIFTMAILER_MAILERS = 'swiftmailer.mailers';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasParameter(self::SWIFTMAILER_MAILERS)) {
            $mailers = $container->getParameter(self::SWIFTMAILER_MAILERS);
            foreach ($mailers as $name => $mailer) {
                $this->configureMailerDecorator($name, $mailer, $container);
            }
        }
    }

    /**
     * @param string           $name
     * @param string           $mailer
     * @param ContainerBuilder $container
     */
    protected function configureMailerDecorator(
        $name,
        $mailer,
        ContainerBuilder $container
    ) {
        $pluginName = sprintf('swiftmailer.mailer.%s.plugin.no_recipient', $name);

        $definitionDecorator = new DefinitionDecorator('swiftmailer.plugin.no_recipient.abstract');
        $container->setDefinition($pluginName, $definitionDecorator);

        $container->getDefinition($mailer)
            ->addMethodCall('registerPlugin', [$container->getDefinition($pluginName)]);
    }
}
