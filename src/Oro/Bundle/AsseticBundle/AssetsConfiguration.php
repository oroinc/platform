<?php

namespace Oro\Bundle\AsseticBundle;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AsseticBundle\Event\Events;
use Oro\Bundle\AsseticBundle\Event\LoadCssEvent;

class AssetsConfiguration
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $rawConfiguration;

    /**
     * @var boolean
     */
    protected $cssLoaded = false;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param array $rawConfiguration
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, array $rawConfiguration)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->rawConfiguration = $rawConfiguration;
    }

    /**
     * Get configuration of CSS assets files
     *
     * @return array
     */
    public function getCss()
    {
        if (!$this->cssLoaded) {
            $event = new LoadCssEvent($this);
            $this->eventDispatcher->dispatch(Events::LOAD_CSS, $event);
            $this->cssLoaded = true;
        }

        return $this->getOption('css', array());
    }

    /**
     * Add CSS
     *
     * @param string $group
     * @param array $files
     */
    public function addCss($group, array $files)
    {
        $css = $this->getOption('css', array());
        if (!isset($css[$group])) {
            $css[$group] = $files;
        } else {
            $css[$group] = array_unique(array_merge($css[$group], $files));
        }
        $this->setOption('css', $css);
    }

    /**
     * Get CSS groups
     *
     * @return array
     */
    public function getCssGroups()
    {
        return array_keys($this->getCss());
    }

    /**
     * Get list of CSS files
     *
     * @param boolean|null $debug
     * @return array
     */
    public function getCssFiles($debug)
    {
        $result = array();

        foreach ($this->getCss() as $group => $files) {
            if (null === $debug
                || (true === $debug && $this->isCssDebugGroup($group))
                || (false === $debug && !$this->isCssDebugGroup($group))
            ) {
                $result = array_merge($result, $files);
            }
        }

        return $result;
    }

    /**
     * @param array $group
     * @return bool
     */
    protected function isCssDebugGroup($group)
    {
        return $this->isCssDebugAll() || in_array($group, $this->getCssDebugGroups());
    }

    /**
     * @return string
     */
    protected function isCssDebugAll()
    {
        return (bool)$this->getOption('css_debug_all', false);
    }

    /**
     * @return array
     */
    public function getCssDebugGroups()
    {
        return (array)$this->getOption('css_debug_groups', array());
    }

    /**
     * Get value of configuration option
     *
     * @param $optionName
     * @param mixed $defaultValue
     * @return mixed
     */
    protected function getOption($optionName, $defaultValue = null)
    {
        if (array_key_exists($optionName, $this->rawConfiguration)) {
            return $this->rawConfiguration[$optionName];
        } else {
            return $defaultValue;
        }
    }

    /**
     * Set value of configuration option
     *
     * @param $optionName
     * @param mixed $value
     */
    protected function setOption($optionName, $value)
    {
        $this->rawConfiguration[$optionName] = $value;
    }
}
