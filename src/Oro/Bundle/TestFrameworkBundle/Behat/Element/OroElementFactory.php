<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
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
     * @param NodeElement $context
     *
     * @return Element
     */
    public function createElement($name, NodeElement $context = null)
    {
        if (!$this->hasElement($name)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find element with "%s" name',
                $name
            ));
        }

        $elementConfig = $this->configuration[$name];

        if ($context) {
            $elementConfig['selector'] = $this->prepend($elementConfig['selector'], $context);
        }

        $element = $this->instantiateElement($elementConfig);
        $this->injectSuite($element);

        return $element;
    }

    /**
     * @param $selector String or array
     * @param NodeElement $element Context element xpath of which will prepend before given selector
     *
     * @return array Xpath selector ['type' => 'xpath', 'locator' => '//']
     */
    protected function prepend($selector, NodeElement $element)
    {
        $xpath = $this->selectorManipulator->prepend(
            $this->selectorManipulator->getSelectorAsXpath($this->selectorsHandler, $selector),
            $element->getXpath()
        );

        return ['type' => 'xpath', 'locator' => $xpath];
    }

    /**
     * Create specific element by name and common NodeElement object
     * Specific element most commonly has more wide interface than NodeElement
     *
     * @param string $name Element name
     * @param NodeElement|null $element
     * @return Element
     * @throws ElementNotFoundException
     */
    public function wrapElement($name, $element)
    {
        if (null === $element) {
            throw new ElementNotFoundException(
                $this->mink->getSession()->getDriver(),
                'OroElement',
                'NodeElement',
                $name
            );
        }

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
     * @param Element $context
     *
     * @return Element
     */
    public function findElementContains($name, $text, Element $context = null)
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

        if ($context) {
            $elementSelector = $this->prepend($elementSelector, $context);
        }

        $element = new $elementClass($this->mink->getSession(), $this, $elementSelector);

        $this
            ->injectSuite($element)
            ->injectOptions($element, $this->configuration[$name]);

        return $element;
    }

    /**
     * @param string $name
     * @param NodeElement|null $context
     * @return Element[]
     */
    public function findAllElements($name, NodeElement $context = null)
    {
        if (!$this->hasElement($name)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find element with "%s" name',
                $name
            ));
        }

        $elementSelector = $this->configuration[$name]['selector'];
        if ($context) {
            $elementSelector = $this->prepend($elementSelector, $context);
        }

        $elements = $this->mink->getSession()->getPage()->findAll(
            $elementSelector['type'],
            $elementSelector['locator']
        );

        return array_map(function (NodeElement $element) use ($name) {
            return $this->wrapElement($name, $element);
        }, $elements);
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
