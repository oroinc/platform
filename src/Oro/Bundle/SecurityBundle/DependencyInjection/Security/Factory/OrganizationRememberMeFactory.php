<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\RememberMeFactory;

class OrganizationRememberMeFactory extends RememberMeFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        /**
         * Add compatibility with Symfony version below 2.8 and current version (2.8)
         */
        $secretKey = isset($config['secret']) ?  $config['secret'] : $config['key'];

        // authentication provider
        $authProviderId = 'oro_security.authentication.provider.organization_rememberme.' . $id;
        $container
            ->setDefinition(
                $authProviderId,
                new DefinitionDecorator('oro_security.authentication.provider.organization_rememberme')
            )
            ->addArgument($secretKey)
            ->addArgument($id);


        // remember me services
        if (isset($config['token_provider'])) {
            $templateId           = 'security.authentication.rememberme.services.persistent';
            $rememberMeServicesId = $templateId . '.' . $id;
        } else {
            $templateId           = 'security.authentication.rememberme.services.simplehash';
            $rememberMeServicesId = $templateId . '.' . $id;
        }

        if ($container->hasDefinition('security.logout_listener.' . $id)) {
            $container
                ->getDefinition('security.logout_listener.' . $id)
                ->addMethodCall('addHandler', array(new Reference($rememberMeServicesId)));
        }

        $rememberMeServices = $container->setDefinition($rememberMeServicesId, new DefinitionDecorator($templateId));
        $rememberMeServices->replaceArgument(1, $secretKey);
        $rememberMeServices->replaceArgument(2, $id);

        if (isset($config['token_provider'])) {
            $rememberMeServices->addMethodCall('setTokenProvider', [new Reference($config['token_provider'])]);
        }

        // remember-me options
        $rememberMeServices->replaceArgument(3, array_intersect_key($config, $this->options));
        // attach to remember-me aware listeners
        $rememberMeServices->replaceArgument(
            0,
            $this->getUserProviders($container, $config, $id, $rememberMeServicesId)
        );

        // remember-me listener
        $listenerId = 'security.authentication.listener.rememberme.' . $id;
        $listener   = $container->setDefinition(
            $listenerId,
            new DefinitionDecorator('security.authentication.listener.rememberme')
        );
        $listener->replaceArgument(1, new Reference($rememberMeServicesId));

        return [$authProviderId, $listenerId, $defaultEntryPoint];
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'organization-remember-me';
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     * @param string           $id
     * @param string           $rememberMeServicesId
     *
     * @return array
     */
    protected function getUserProviders(ContainerBuilder $container, $config, $id, $rememberMeServicesId)
    {
        $userProviders = [];
        foreach ($container->findTaggedServiceIds('security.remember_me_aware') as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['id']) || $attribute['id'] !== $id) {
                    continue;
                }

                if (!isset($attribute['provider'])) {
                    throw new \RuntimeException(
                        'Each "security.remember_me_aware" tag must have a provider attribute.'
                    );
                }

                $userProviders[] = new Reference($attribute['provider']);
                $container
                    ->getDefinition($serviceId)
                    ->addMethodCall('setRememberMeServices', array(new Reference($rememberMeServicesId)));
            }
        }
        if ($config['user_providers']) {
            $userProviders = [];
            foreach ($config['user_providers'] as $providerName) {
                $userProviders[] = new Reference('security.user.provider.concrete.' . $providerName);
            }
        }
        if (count($userProviders) === 0) {
            throw new \RuntimeException(
                'You must configure at least one remember-me aware listener (such as form-login)
                for each firewall that has organization-remember-me enabled.'
            );
        }

        return $userProviders;
    }
}
