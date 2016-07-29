<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Mink;
use Behat\Testwork\Suite\Suite;

class OroElementFactory implements SuiteAwareInterface
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
     * @var Suite
     */
    private $suite;

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
     * @return bool
     */
    public function hasElement($name)
    {
        return array_key_exists($name, $this->configuration);
    }

    /**
     * @param string $name
     *
     * @return Element
     */
    public function createElement($name)
    {
        if (!$this->hasElement($name)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find element with "%s" name',
                $name
            ));
        }

        $element = $this->instantiateElement($this->configuration[$name]);
        $this->injectSuite($element);

        return $element;
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
        if (!$this->hasElement($name)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find element with "%s" name',
                $name
            ));
        }

        $elementClass = $this->configuration[$name]['class'];

        $element = new $elementClass(
            $this->mink->getSession(),
            $this,
            ['type' => 'xpath', 'locator' => $element->getXpath()]
        );
        $this->injectSuite($element);

        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public function setSuite(Suite $suite)
    {
        $this->suite = $suite;
    }

    /**
     * @param NodeElement $element
     */
    protected function injectSuite(NodeElement $element)
    {
        if ($element instanceof SuiteAwareInterface) {
            $element->setSuite($this->suite);
        }
    }

    /**
     * @param array $elementConfig
     *
     * @return Element
     */
    protected function instantiateElement(array $elementConfig)
    {
        $elementClass = $elementConfig['class'];

        /** @var Element $element */
        $element = new $elementClass($this->mink->getSession(), $this, $elementConfig['selector']);

        if (isset($elementConfig['options'])) {
            $element->setOptions($elementConfig['options']);
        }

        return $element;
    }
}
