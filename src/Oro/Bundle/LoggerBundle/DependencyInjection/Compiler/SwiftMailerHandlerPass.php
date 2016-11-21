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
                $this->configureMailerDecorator($name, $container);
            }
        }
    }

    /**
     * @param string $name
     * @param ContainerBuilder $container
     */
    protected function configureMailerDecorator(
        $name,
        ContainerBuilder $container
    ) {
        $definitionDecorator = new DefinitionDecorator('swiftmailer.plugin.no_recipient.abstract');
        $container->setDefinition(sprintf('swiftmailer.mailer.%s.plugin.no_recipient', $name), $definitionDecorator);
        $container->getDefinition(sprintf('swiftmailer.mailer.%s.plugin.no_recipient', $name))->addTag(
            sprintf('swiftmailer.%s.plugin', $name)
        );
    }
}
