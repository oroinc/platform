<?php

namespace Oro\Bundle\InstallerBundle\Composer;

use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as SensioScriptHandler;
use Symfony\Component\Filesystem\Filesystem;
use Composer\Script\CommandEvent;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ScriptHandler extends SensioScriptHandler implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Installs the assets for installer bundle
     *
     * @param CommandEvent $event A instance
     */
    public static function installAssets(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $webDir  = $options['symfony-web-dir'];

        //$sourceDir = __DIR__ . '/../Resources/public';
        $sourceDir = self::getContainer()
            ->get('kernel')
            ->locateResource('@OroInstallerBundle/Resources/public');
        $targetDir = $webDir . '/bundles/oroinstaller';

        $filesystem = new Filesystem();
        $filesystem->remove($targetDir);
        $filesystem->mirror($sourceDir, $targetDir);
    }

    /**
     * Set permissions for directories
     *
     * @param CommandEvent $event
     */
    public static function setPermissions(CommandEvent $event)
    {
        $options = self::getOptions($event);

        $webDir = isset($options['symfony-web-dir']) ?
            $options['symfony-web-dir'] : 'web';

        $parametersFile = isset($options['incenteev-parameters']['file']) ?
            $options['incenteev-parameters']['file'] : 'app/config/parameters.yml';

        $directories = [
            'app/cache',
            'app/logs',
            'app/attachment',
            $webDir,
            $parametersFile
        ];

        $permissionHandler = new PermissionsHandler();
        foreach ($directories as $directory) {
            $permissionHandler->setPermissions($directory);
        }
    }
}
