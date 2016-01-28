<?php

namespace Oro\Bundle\InstallerBundle\Composer;

use Composer\IO\IOInterface;

class AssetsVersionHandler
{
    const ASSETS_VERSION          = 'assets_version';
    const ASSETS_VERSION_STRATEGY = 'assets_version_strategy';

    /** @var IOInterface */
    protected $io;

    /**
     * @param IOInterface $io
     */
    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * @param array $parameters
     * @param array $options
     *
     * @return bool
     */
    public function setAssetsVersion(array &$parameters, array $options)
    {
        $assetsVersion               = null;
        $assetsVersionExists         = false;
        $assetsVersionStrategy       = null;
        $assetsVersionStrategyExists = false;
        if (array_key_exists(self::ASSETS_VERSION, $parameters)) {
            $assetsVersion       = $parameters[self::ASSETS_VERSION];
            $assetsVersionExists = true;
        }
        if (array_key_exists(self::ASSETS_VERSION_STRATEGY, $parameters)) {
            $assetsVersionStrategy       = $parameters[self::ASSETS_VERSION_STRATEGY];
            $assetsVersionStrategyExists = true;
        }

        $hasChanges = false;
        if (!$assetsVersionExists || !$this->isEnvironmentVariable($options, self::ASSETS_VERSION)) {
            $assetsVersion = $this->generateAssetsVersion($assetsVersionStrategy, $assetsVersion);
            if (!$assetsVersionExists || null !== $assetsVersion) {
                $hasChanges = true;
                $this->io->write(
                    sprintf('<info>Updating the "%s" parameter</info>', self::ASSETS_VERSION)
                );
                $parameters[self::ASSETS_VERSION] = $assetsVersion;
            }
        }
        if (!$assetsVersionStrategyExists) {
            $hasChanges = true;
            $this->io->write(
                sprintf('<info>Initializing the "%s" parameter</info>', self::ASSETS_VERSION_STRATEGY)
            );
            $parameters[self::ASSETS_VERSION_STRATEGY] = $assetsVersionStrategy;
        }

        return $hasChanges;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function getEnvironmentVariablesMap($options)
    {
        return isset($options['incenteev-parameters']['env-map'])
            ? $options['incenteev-parameters']['env-map']
            : [];
    }

    /**
     * @param array  $options
     * @param string $variableName
     *
     * @return bool
     */
    protected function isEnvironmentVariable($options, $variableName)
    {
        $envMap = $this->getEnvironmentVariablesMap($options);

        return array_key_exists($variableName, $envMap);
    }

    /**
     * @param string      $strategy
     * @param string|null $currentVersion
     *
     * @return string|null
     */
    protected function generateAssetsVersion($strategy, $currentVersion)
    {
        if ('time_hash' === $strategy) {
            return substr(md5(date('c')), 0, 8);
        }
        if ('incremental' === $strategy) {
            return $this->incrementVersion(null !== $currentVersion ? $currentVersion : '0');
        }

        return null;
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function incrementVersion($version)
    {
        return preg_replace_callback(
            '/^(.*?)(\d+)(.*?)$/',
            function ($match) {
                return $match[1] . ((int)$match[2] + 1) . $match[3];
            },
            $version
        );
    }
}
