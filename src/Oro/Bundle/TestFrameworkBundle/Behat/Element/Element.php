<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Session;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\AssertTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;

class Element extends NodeElement
{
    use AssertTrait;

    /**
     * @var OroElementFactory
     */
    protected $elementFactory;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var SelectorManipulator
     */
    protected $selectorManipulator;

    /**
     * @param Session $session
     * @param OroElementFactory $elementFactory
     * @param array|string $selector
     */
    public function __construct(
        Session $session,
        OroElementFactory $elementFactory,
        $selector = ['type' => 'xpath', 'locator' => '/html/body']
    ) {
        $this->elementFactory = $elementFactory;
        $this->session = $session;
        $this->selectorManipulator = new SelectorManipulator();

        parent::__construct(
            $this->selectorManipulator->getSelectorAsXpath($session->getSelectorsHandler(), $selector),
            $session
        );
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param string $name
     * @param array  $arguments
     */
    public function __call($name, $arguments)
    {
        $message = sprintf('"%s" method is not available on the %s', $name, $this->getName());

        throw new \BadMethodCallException($message);
    }

    /**
     * Finds label with specified locator.
     *
     * @param string $locator label text
     *
     * @return Element|null
     */
    public function findLabel($text)
    {
        return $this->find('css', $this->selectorManipulator->addContainsSuffix('label', $text));
    }

    /**
     * Find first visible element
     *
     * @param string       $selector selector engine name
     * @param string|array $locator  selector locator
     *
     * @return NodeElement|null
     */
    public function findVisible($selector, $locator)
    {
        $visibleElements = array_filter(
            $this->getPage()->findAll($selector, $locator),
            function (NodeElement $element) {
                return $element->isVisible();
            }
        );

        return array_shift($visibleElements);
    }

    /**
     * @param string $name Element name
     *
     * @return Element
     */
    public function getElement($name)
    {
        return $this->elementFactory->createElement($name, $this);
    }

    /**
     * @param string $name
     * @param string $text
     *
     * @return Element
     */
    public function findElementContains($name, $text)
    {
        return $this->elementFactory->findElementContains($name, $text, $this);
    }

    /**
     * Returns element's driver.
     *
     * @return OroSelenium2Driver
     */
    protected function getDriver()
    {
        return parent::getDriver();
    }

    /**
     * @return DocumentElement
     */
    protected function getPage()
    {
        return $this->session->getPage();
    }

    /**
     * @return string
     */
    protected function getName()
    {
        return preg_replace('/^.*\\\(.*?)$/', '$1', get_class($this));
    }
}
