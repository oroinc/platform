<?php

namespace Oro\Bundle\InstallerBundle\Composer;

use Composer\Script\Event;
use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as SensioScriptHandler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

/**
 * Installs the assets for installer bundle, bower and npm dependencies
 */
class ScriptHandler extends SensioScriptHandler
{
    /**
     * Installs the assets for installer bundle, bower and npm dependencies
     *
     * @param Event $event A instance
     */
    public static function installAssets(Event $event)
    {
        $options = self::getOptions($event);
        $webDir  = $options['symfony-web-dir'];
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        $config = [
            [
                'from' => $vendorDir.'/bower-asset',
                'to' => $webDir.'/bundles/bowerassets',
            ],
            [
                'from' => $vendorDir.'/npm-asset',
                'to' => $webDir.'/bundles/npmassets',
            ],
        ];

        $filesystem = new Filesystem();

        foreach ($config as $item) {
            if ($filesystem->exists($item['from'])) {
                $filesystem->remove($item['to']);
                $filesystem->mirror($item['from'], $item['to']);
            }
        }
    }

    /**
     * Set permissions for directories
     *
     * @param Event $event
     */
    public static function setPermissions(Event $event)
    {
        $options = self::getOptions($event);

        $webDir = isset($options['symfony-web-dir']) ?
            $options['symfony-web-dir'] : 'web';

        $parametersFile = self::getParametersFile($options);

        $directories = [
            'var/cache',
            'var/logs',
            'var/attachment',
            $webDir,
            $parametersFile
        ];

        $permissionHandler = new PermissionsHandler();
        foreach ($directories as $directory) {
            $permissionHandler->setPermissions($directory);
        }
        if (file_exists($importExportDir = 'var/import_export')) {
            $permissionHandler->setPermissions($importExportDir);
        }
    }

    /**
     * Sets the global assets version
     *
     * @param Event $event A instance
     */
    public static function setAssetsVersion(Event $event)
    {
        $options = self::getOptions($event);

        $parametersFile = self::getParametersFile($options);
        if (is_file($parametersFile) && is_writable($parametersFile)) {
            $values               = self::loadParametersFile($parametersFile);
            $parametersKey        = self::getParametersKey($options);
            $assetsVersionHandler = new AssetsVersionHandler($event->getIO());
            if (isset($values[$parametersKey])
                && $assetsVersionHandler->setAssetsVersion($values[$parametersKey], $options)
            ) {
                self::saveParametersFile($parametersFile, $values);
            }
        } else {
            $event->getIO()->write(
                sprintf(
                    '<comment>Cannot set assets version because "%s" file does not exist or not writable</comment>',
                    $parametersFile
                )
            );
        }
    }

    /**
     * @param string $parametersFile
     *
     * @return array
     */
    protected static function loadParametersFile($parametersFile)
    {
        $yamlParser = new Parser();

        return $yamlParser->parse(file_get_contents($parametersFile));
    }

    /**
     * @param string $parametersFile
     * @param array  $values
     */
    protected static function saveParametersFile($parametersFile, array $values)
    {
        file_put_contents(
            $parametersFile,
            "# This file is auto-generated during the composer install\n" . Yaml::dump($values, 99)
        );
    }

    /**
     * @param array $options
     *
     * @return string
     */
    protected static function getParametersFile($options)
    {
        return isset($options['incenteev-parameters']['file'])
            ? $options['incenteev-parameters']['file']
            : 'config/parameters.yml';
    }

    /**
     * @param array $options
     *
     * @return string
     */
    protected static function getParametersKey($options)
    {
        return isset($options['incenteev-parameters']['parameter-key'])
            ? $options['incenteev-parameters']['parameter-key']
            : 'parameters';
    }
}
