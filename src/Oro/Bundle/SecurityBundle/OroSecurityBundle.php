<?php

namespace Oro\Bundle\SecurityBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AccessRulesPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclAnnotationProviderPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclConfigurationPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclGroupProvidersPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclPrivilegeFilterPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\DecorateAuthorizationCheckerPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\OwnerMetadataProvidersPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\OwnershipDecisionMakerPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\OwnershipTreeProvidersPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\RemoveAclSchemaListenerPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\SetPublicForDecoratedAuthorizationCheckerPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory\OrganizationFormLoginFactory;
use Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory\OrganizationHttpBasicFactory;
use Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory\OrganizationRememberMeFactory;
use Oro\Bundle\SecurityBundle\DoctrineExtension\Dbal\Types\CryptedStringType;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass;
use Symfony\Bundle\MonologBundle\DependencyInjection\Compiler\LoggerChannelPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Security bundle that responsible for security access checks to data.
 */
class OroSecurityBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AclConfigurationPass());
        $container->addCompilerPass(new AclAnnotationProviderPass());
        $container->addCompilerPass(new OwnershipDecisionMakerPass());
        $container->addCompilerPass(new OwnerMetadataProvidersPass());
        $container->addCompilerPass(new OwnershipTreeProvidersPass());
        $container->addCompilerPass(new AclGroupProvidersPass());
        $container->addCompilerPass(new AclPrivilegeFilterPass());
        $container->addCompilerPass(new AccessRulesPass());
        if ($container instanceof ExtendedContainerBuilder) {
            $container->addCompilerPass(new RemoveAclSchemaListenerPass());
            $container->moveCompilerPassBefore(
                RemoveAclSchemaListenerPass::class,
                RegisterEventListenersAndSubscribersPass::class
            );
            $container->addCompilerPass(new DecorateAuthorizationCheckerPass());
            $container->moveCompilerPassBefore(
                DecorateAuthorizationCheckerPass::class,
                LoggerChannelPass::class
            );
            $container->addCompilerPass(
                new SetPublicForDecoratedAuthorizationCheckerPass(),
                PassConfig::TYPE_BEFORE_REMOVING
            );
        }

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new OrganizationFormLoginFactory());
        $extension->addSecurityListenerFactory(new OrganizationHttpBasicFactory());
        $extension->addSecurityListenerFactory(new OrganizationRememberMeFactory());

        if ('test' === $container->getParameter('kernel.environment')) {
            $container->addCompilerPass(
                DoctrineOrmMappingsPass::createAnnotationMappingDriver(
                    ['Oro\Bundle\SecurityBundle\Tests\Functional\Environment\Entity'],
                    [$this->getPath() . '/Tests/Functional/Environment/Entity']
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        CryptedStringType::setCrypter($this->container->get('oro_security.encoder.repetitive_crypter'));
    }
}
