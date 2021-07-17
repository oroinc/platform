<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory;

use Oro\Bundle\SecurityBundle\Authentication\Listener\RememberMeListener;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\RememberMeFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures services for "Remember Me" login functionality.
 */
class OrganizationRememberMeFactory extends RememberMeFactory
{
    public function __construct()
    {
        $this->options['csrf_protected_mode'] = false;
    }

    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        [$authProviderId, $innerListenerId, $defaultEntryPoint] = parent::create(
            $container,
            $id,
            $config,
            $userProvider,
            $defaultEntryPoint
        );

        // authentication provider
        $container->removeDefinition($authProviderId);
        $authProviderId = 'oro_security.authentication.provider.organization_rememberme.' . $id;
        $container
            ->setDefinition(
                $authProviderId,
                new ChildDefinition('oro_security.authentication.provider.organization_rememberme')
            )
            ->addArgument($config['secret'])
            ->addArgument($id);

        // remember-me listener
        $listenerId = 'oro_security.authentication.listener.rememberme.' . $id;
        $listener = $container
            ->register($listenerId, RememberMeListener::class)
            ->setArguments([
                new Reference($innerListenerId),
                new Reference('session', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
            ])
            ->addMethodCall('setCsrfRequestManager', [new Reference('oro_security.csrf_request_manager')]);
        // point if listener processes CSRF protected AJAX requests only
        if ($config['csrf_protected_mode'] === true) {
            $listener->addMethodCall('switchToProcessAjaxCsrfOnlyRequest');
        }

        return [$authProviderId, $listenerId, $defaultEntryPoint];
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'organization-remember-me';
    }
}
