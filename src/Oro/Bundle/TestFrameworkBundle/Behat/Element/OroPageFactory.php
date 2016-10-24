<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Mink;
use Behat\Mink\Session;
use Symfony\Component\Routing\Router;

class OroPageFactory
{
    /**
     * @var OroElementFactory
     */
    protected $elementFactory;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param OroElementFactory $elementFactory
     * @param array $config
     */
    public function __construct(OroElementFactory $elementFactory, array $config)
    {
        $this->elementFactory = $elementFactory;
        $this->config = $config;
    }

    /**
     * @param $name
     * @return Page
     */
    public function getPage($name)
    {
        if (!$this->hasPage($name)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find page with "%s" name',
                $name
            ));
        }

        $pageConfig = $this->config[$name];

        return new $pageConfig['class']($this->elementFactory, $pageConfig['route']);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasPage($name)
    {
        return array_key_exists($name, $this->config);
    }
}
