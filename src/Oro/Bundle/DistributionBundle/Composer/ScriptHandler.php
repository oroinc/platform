<?php
namespace Oro\Bundle\DistributionBundle\Composer;

use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as BaseScriptHandler;
use Composer\Script\CommandEvent;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler extends BaseScriptHandler
{
    public static function makePlatformPackageSymlink(CommandEvent $event)
    {
        $sourceDir = 'vendor/oro/platform-dist';
        $targetDir = 'packages/oro/platform-dist';

        $filesystem = new Filesystem();
        $filesystem->remove($targetDir);
        $filesystem->mirror($sourceDir, $targetDir);
    }
} 