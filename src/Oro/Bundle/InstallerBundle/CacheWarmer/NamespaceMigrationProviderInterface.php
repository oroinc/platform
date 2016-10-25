<?php

namespace Oro\Bundle\InstallerBundle\CacheWarmer;

interface NamespaceMigrationProviderInterface
{
    /**
     * Config changes for fix database defenitions
     *
     * @return array
     */
    public function getConfig();
}
