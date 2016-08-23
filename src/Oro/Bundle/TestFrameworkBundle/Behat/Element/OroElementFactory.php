<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Mink;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Testwork\Suite\Suite;

class OroElementFactory implements SuiteAwareInterface
{
    /**
     * @var Mink
     */
    private $mink = null;

    /**
     * @var SelectorsHandler
     */
    private $selectorsHandler;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @var Suite
     */
    private $suite;

    /**
     * @var SelectorManipulator
     */
    private $selectorManipulator;

    /**
     * @param Mink $mink
     * @param SelectorsHandler $selectorsHandler
     * @param array $configuration
     */
    public function __construct(Mink $mink, SelectorsHandler $selectorsHandler, array $configuration)
    {
        $this->mink = $mink;
        $this->configuration = $configuration;
        $this->selectorsHandler = $selectorsHandler;
        $this->selectorManipulator = new SelectorManipulator();
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
     * @param string $name Element name
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
     * @param string $name Element name
     * @param NodeElement $element
     *
     * @return Element
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
     * @param string $name Element name
     * @param string $text Text that contains in element node
     *
     * @return Element
     */
    public function findElementContains($name, $text)
    {
        if (!$this->hasElement($name)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find element with "%s" name',
                $name
            ));
        }

        $elementClass = $this->configuration[$name]['class'];
        $elementSelector = $this->selectorManipulator->addContainsSuffix(
            $this->configuration[$name]['selector'],
            $text
        );

        $element = new $elementClass($this->mink->getSession(), $this, $elementSelector);

        $this
            ->injectSuite($element)
            ->injectOptions($element, $this->configuration[$name]);

        return $element;
    }

    /**
     * @return Element
     */
    public function getPage()
    {
        return new Element($this->mink->getSession(), $this, ['type' => 'xpath', 'locator' => '/html/body']);
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
     *
     * @return $this
     */
    protected function injectSuite(NodeElement $element)
    {
        if ($element instanceof SuiteAwareInterface) {
            $element->setSuite($this->suite);
        }

        return $this;
    }

    /**
     * @param Element $element
     * @param array $elementConfig
     *
     * @return $this
     */
    protected function injectOptions(Element $element, array $elementConfig)
    {
        if (array_key_exists('options', $elementConfig)) {
            $element->setOptions($elementConfig['options']);
        }

        return $this;
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

        $this->injectOptions($element, $elementConfig);

        return $element;
    }
}
