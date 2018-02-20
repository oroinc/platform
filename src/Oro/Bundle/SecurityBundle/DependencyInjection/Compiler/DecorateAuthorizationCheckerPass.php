<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\Authorization\AuthorizationChecker;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DecorateAuthorizationCheckerPass implements CompilerPassInterface
{
    const AUTHORIZATION_CHECKER = 'oro_security.authorization_checker';
    const DECORATED_AUTHORIZATION_CHECKER = 'oro_security.authorization_checker.inner';
    const DEFAULT_AUTHORIZATION_CHECKER = 'security.authorization_checker';
    const ACL_OBJECT_IDENTITY_FACTORY_LINK = 'oro_security.acl.object_identity_factory.link';
    const ACL_ANNOTATION_PROVIDER_LINK = 'oro_security.acl.annotation_provider.link';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->configureAuthorizationChecker($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function configureAuthorizationChecker(ContainerBuilder $container)
    {
        $container->register(self::AUTHORIZATION_CHECKER, AuthorizationChecker::class)
            ->setPublic(false)
            ->setDecoratedService(self::DEFAULT_AUTHORIZATION_CHECKER, self::DECORATED_AUTHORIZATION_CHECKER)
            ->setArguments([
                $this->registerServiceLink($container, self::DECORATED_AUTHORIZATION_CHECKER),
                new Reference(self::ACL_OBJECT_IDENTITY_FACTORY_LINK),
                new Reference(self::ACL_ANNOTATION_PROVIDER_LINK),
                new Reference('logger')
            ])
            ->addTag('monolog.logger', ['channel' => 'security']);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $serviceId
     *
     * @return Reference
     */
    protected function registerServiceLink(ContainerBuilder $container, $serviceId)
    {
        $linkServiceId = $serviceId . '.link';
        $container
            ->register($linkServiceId, ServiceLink::class)
            ->setPublic(false)
            ->setArguments([new Reference('service_container'), $serviceId]);

        return new Reference($linkServiceId);
    }
}
