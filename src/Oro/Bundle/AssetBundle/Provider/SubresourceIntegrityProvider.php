<?php

namespace Oro\Bundle\AssetBundle\Provider;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Provides subresource integrity hashes from the json files.
 */
class SubresourceIntegrityProvider
{
    protected const string BUILD_ASSET_PATH = '/public/build';
    protected const string INTEGRITY_FILE_MASK = '/public/build/%s/integrity.json';

    public function __construct(private CacheItemPoolInterface $integrityCache, private string $projectDir)
    {
    }

    public function warmUpCache(): void
    {
        $themeDirNames = $this->getThemeDirNames();
        foreach ($themeDirNames as $themeDirName) {
            $integrityData = $this->getIntegrityForTheme($themeDirName);

            foreach ($integrityData as $key => $value) {
                $hashKey = $this->getHashKey($key);

                if (!$this->integrityCache->getItem($hashKey)->isHit()) {
                    $item = $this->integrityCache->getItem($hashKey)->set($value);
                    $this->integrityCache->save($item);
                }
            }
        }
    }

    public function getHash(string $assetName): ?string
    {
        $hashKey = $this->getHashKey($assetName);
        if ($this->integrityCache->getItem($hashKey)->isHit()) {
            return $this->integrityCache->getItem($hashKey)->get();
        }
        $themeDirName = $this->extractThemeName($assetName);
        $integrityData = $this->getIntegrityForTheme($themeDirName);

        foreach ($integrityData as $key => $value) {
            $hashKey = $this->getHashKey($key);
            $item = $this->integrityCache->getItem($hashKey)->set($value);
            $this->integrityCache->save($item);
        }

        return $integrityData[$assetName] ?? null;
    }

    protected function getIntegrityForTheme(string $themeDirName): array
    {
        $integrityThemeFilePath = $this->projectDir . sprintf(self::INTEGRITY_FILE_MASK, $themeDirName);
        if (!file_exists($integrityThemeFilePath)) {
            return [];
        }
        $jsonContent = file_get_contents($integrityThemeFilePath);

        return json_decode($jsonContent, true, flags: JSON_THROW_ON_ERROR);
    }

    protected function extractThemeName(string $assetName): ?string
    {
        $pathParts = explode('/', $assetName);

        return $pathParts[2] ?? null;
    }

    protected function getHashKey(string $assetName): string
    {
        return md5($assetName);
    }

    protected function getThemeDirNames(): array
    {
        $filesystem = new Filesystem();
        $finder = new Finder();
        $buildDirPath = $this->projectDir . self::BUILD_ASSET_PATH;
        $directories = [];

        if (!$filesystem->exists($buildDirPath)) {
            return $directories;
        }
        $finder->directories()->depth('== 0')->in($buildDirPath);
        foreach ($finder as $dir) {
            $directories[] = $dir->getRelativePathname();
        }

        return $directories;
    }
}
