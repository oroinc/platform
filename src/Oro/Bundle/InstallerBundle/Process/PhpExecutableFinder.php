<?php

namespace Oro\Bundle\InstallerBundle\Process;

use Symfony\Component\Process\PhpExecutableFinder as BasePhpExecutableFinder;

class PhpExecutableFinder extends BasePhpExecutableFinder
{
    /**
     * {@inheritdoc}
     */
    public function find()
    {
        if ($php = getenv('ORO_PHP_PATH')) {
            if (is_executable($php)) {
                return $php;
            }
        }

        return parent::find();
    }
}
