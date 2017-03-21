<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Oro\Bundle\InstallerBundle\CacheWarmer\NamespaceMigrationProviderInterface;

class NamespaceMigrationProvider implements NamespaceMigrationProviderInterface
{
    /** @var string[] */
    protected $additionConfig
        = [
            'OroCRM\Bundle\SalesBundle\Entity\B2bCustomer' =>
                'Oro\Bundle\SalesBundle\Entity\B2bCustomer',
        ];

    /**
     * (@inheritdoc}
     */
    public function getConfig()
    {
        return $this->additionConfig;
    }
}
