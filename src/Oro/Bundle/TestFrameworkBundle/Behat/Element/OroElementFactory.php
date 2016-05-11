<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Mink;

class OroElementFactory
{
    /**
     * @var Mink
     */
    private $mink = null;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @param Mink  $mink
     * @param array $configuration
     */
    public function __construct(Mink $mink, array $configuration)
    {
        $this->mink = $mink;
        $this->configuration = $configuration;
    }

    /**
     * @param string $name
     *
     * @return Element
     */
    public function createElement($name)
    {
        if (false === array_key_exists($name, $this->configuration)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find element with "%s" name',
                $name
            ));
        }

        return $this->instantiateElement($this->configuration[$name]);
    }

    /**
     * @param array $elementConfig
     *
     * @return Element
     */
    private function instantiateElement(array $elementConfig)
    {
        $elementClass = $elementConfig['class'];

        return new $elementClass($this->mink->getSession(), $this, $elementConfig['selector']);
    }
}
