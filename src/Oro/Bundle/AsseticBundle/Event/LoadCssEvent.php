<?php

namespace Oro\Bundle\AsseticBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\AsseticBundle\AssetsConfiguration;

class LoadCssEvent extends Event
{
    /**
     * @var AssetsConfiguration
     */
    protected $assetsConfiguration;

    /**
     * @param AssetsConfiguration $assetsConfiguration
     */
    public function __construct(AssetsConfiguration $assetsConfiguration)
    {
        $this->assetsConfiguration = $assetsConfiguration;
    }

    /**
     * Add CSS files.
     *
     * @param string $group
     * @param array $files
     */
    public function addCss($group, array $files)
    {
        $this->assetsConfiguration->addCss($group, $files);
    }
}
