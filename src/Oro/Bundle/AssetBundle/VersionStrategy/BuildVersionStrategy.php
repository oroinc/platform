<?php
namespace Oro\Bundle\AssetBundle\VersionStrategy;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

/**
 * Assets version strategy based on value taken from 'public/build/build_version.txt' file.
 */
class BuildVersionStrategy implements VersionStrategyInterface
{
    private const FORMAT = '%s?v=%s';

    private ?string $version = null;

    private string $buildVersionFilePath;

    /**
     * @param string $buildVersionFilePath
     */
    public function __construct(string $buildVersionFilePath)
    {
        $this->buildVersionFilePath = $buildVersionFilePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion($path): string
    {
        if ($this->version === null) {
            $version = '';

            if (file_exists($this->buildVersionFilePath)) {
                $version = preg_replace('/\\s+/', '', (string) file_get_contents($this->buildVersionFilePath));
            }

            $this->version = $version;
        }

        return $this->version;
    }

    /**
     * {@inheritdoc}
     */
    public function applyVersion($path): string
    {
        $version = $this->getVersion($path);
        if ($version === '') {
            return $path;
        }
        $versioned = sprintf(self::FORMAT, ltrim($path, '/'), $version);

        if ($path && '/' == $path[0]) {
            return '/'.$versioned;
        }

        return $versioned;
    }
}
