<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Symfony\Component\Config\FileLocator;

interface TranslationPackagesProviderExtensionInterface
{
    /**
     * @return array|string[]
     */
    public function getPackageNames();

    /**
     * @return FileLocator
     */
    public function getPackagePaths();
}
