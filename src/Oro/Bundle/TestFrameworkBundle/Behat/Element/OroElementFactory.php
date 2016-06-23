<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Element\NodeElement;
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
     * Create specific element by name and common NodeElement object
     * Specific element most commonly has more wide interface than NodeElement
     *
     * @param string $name
     * @param NodeElement $element
     * @return NodeElement
     */
    public function wrapElement($name, NodeElement $element)
    {
        if (false === array_key_exists($name, $this->configuration)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find element with "%s" name',
                $name
            ));
        }

        $elementClass = $this->configuration[$name]['class'];

        return new $elementClass(
            $this->mink->getSession(),
            $this,
            ['type' => 'xpath', 'locator' => $element->getXpath()]
        );
    }

    /**
     * @param array $elementConfig
     *
     * @return Element
     */
    protected function instantiateElement(array $elementConfig)
    {
        $elementClass = $elementConfig['class'];

        $element = new $elementClass($this->mink->getSession(), $this, $elementConfig['selector']);

        if (isset($elementConfig['options'])) {
            $element->setOptions($elementConfig['options']);
        }

        return $element;
    }
}
