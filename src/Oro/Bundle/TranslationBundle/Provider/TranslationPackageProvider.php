<?php

namespace Oro\Bundle\TranslationBundle\Provider;

/**
 * The registry of translation packages.
 */
class TranslationPackageProvider implements PackageProviderInterface
{
    /** @var iterable|TranslationPackagesProviderExtensionInterface[] */
    private $extensions;

    /**
     * @param iterable|TranslationPackagesProviderExtensionInterface[] $extensions
     */
    public function __construct(iterable $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * @return array
     */
    public function getInstalledPackages()
    {
        $packages = [];
        foreach ($this->extensions as $extension) {
            $packages[] = $extension->getPackageNames();
        }
        if ($packages) {
            $packages = array_unique(array_merge(...$packages));
        }

        return $packages;
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
