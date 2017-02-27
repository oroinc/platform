<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Symfony\Component\Config\FileLocator;

class TranslationPackagesProviderExtension implements TranslationPackagesProviderExtensionInterface
{
    const PACKAGE_NAME = 'Oro';

    /**
     * {@inheritdoc}
     */
    public function getPackageNames()
    {
        return [self::PACKAGE_NAME];
    }

    /**
     * {@inheritdoc}
     */
    public function getPackagePaths()
    {
        return new FileLocator(__DIR__ . '/../../../../');
    }
}
