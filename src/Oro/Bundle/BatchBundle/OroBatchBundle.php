<?php

namespace Oro\Bundle\BatchBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Component\Config\CumulativeResourceManager;

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
        CumulativeResourceManager::getInstance()->registerResource(
            $this->getName(),
            'Resources/config/batch_jobs.yml'
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
