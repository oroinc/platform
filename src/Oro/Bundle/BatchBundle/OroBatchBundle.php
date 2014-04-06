<?php

namespace Oro\Bundle\BatchBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

/**
 * Batch Bundle
 *
 */
class OroBatchBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->addResourceLoader(
            $this->getName(),
            new YamlCumulativeFileLoader('Resources/config/batch_jobs.yml')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'AkeneoBatchBundle';
    }
}
