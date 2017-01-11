<?php

namespace Oro\Bundle\TranslationBundle\Provider;

class TranslationPackageProvider implements PackageProviderInterface
{
    /** @var array|TranslationPackagesProviderExtensionInterface[] */
    protected $extensions = [];

    /**
     * @param TranslationPackagesProviderExtensionInterface $extension
     */
    public function addExtension(TranslationPackagesProviderExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * @return array
     */
    public function getInstalledPackages()
    {
        $packages = [];

        foreach ($this->extensions as $extension) {
            $packages = array_merge($packages, $extension->getPackageNames());
        }

        return array_unique($packages);
    }

    /**
     * @param string $packageName
     *
     * @return TranslationPackagesProviderExtensionInterface|null
     */
    public function getTranslationPackageProviderByPackageName($packageName)
    {
        foreach ($this->extensions as $extension) {
            if (in_array($packageName, $extension->getPackageNames(), true)) {
                return $extension;
            }
        }

        return null;
    }
}
