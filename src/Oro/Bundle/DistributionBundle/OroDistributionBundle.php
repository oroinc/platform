<?php

namespace Oro\Bundle\DistributionBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroDistributionBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        // avoid exception in Composer\Factory for creation service oro_distribution.composer
        if (!getenv('COMPOSER_HOME') && !getenv('HOME')) {
            $kernelRootDir = $this->container->getParameter('kernel.root_dir');
            putenv(sprintf('COMPOSER_HOME=%s/cache/composer', $kernelRootDir));
            chdir(realpath($kernelRootDir . '/../'));
        }
    }
}
