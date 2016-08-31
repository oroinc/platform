<?php

namespace Oro\Component\Config\Dumper;

use Symfony\Component\DependencyInjection\ContainerBuilder;

interface ConfigMetadataDumperInterface
{
    /**
     * Write meta file with resources related to specific config type
     *
     * @param ContainerBuilder $container container with resources to dump
     */
    public function dump(ContainerBuilder $container);

    /**
     * Check are config resources fresh?
     *
     * @return bool true if data in cache is present and up to date, false otherwise
     */
    public function isFresh();
}
