<?php

namespace Oro\Bundle\FeatureToggleBundle;

use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\FeatureToggleablePass;
use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\FeatureToggleVotersPass;
use Oro\Bundle\FeatureToggleBundle\DependencyInjection\OroFeatureToggleExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroFeatureToggleBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new FeatureToggleVotersPass());
        $container->addCompilerPass(new FeatureToggleablePass());
    }

    #[\Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new OroFeatureToggleExtension();
        }

        return $this->extension;
    }
}
