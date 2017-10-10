<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Symfony\Component\Config\FileLocator;

class TranslationPackagesProviderExtension implements TranslationPackagesProviderExtensionInterface
{
    const PACKAGE_NAME = 'Oro';

    /**
     * @var string
     */
    private $rootDirectory;

    /**
     * @param string $rootDirectory
     */
    public function __construct($rootDirectory)
    {
        $this->rootDirectory = $rootDirectory;
    }

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
        return new FileLocator([
            $this->rootDirectory . '/../vendor/oro/platform/src',
            $this->rootDirectory . '/../vendor/oro/calendar-bundle',
            $this->rootDirectory . '/../vendor/oro/platform-serialised-fields/src',
        ]);
    }
}
