<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Session;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\AssertTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\SpinTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
use WebDriver\Exception\ElementNotVisible;
use WebDriver\Exception\NoSuchElement;

/**
 * Base page element node.
 *
 * @method OroSelenium2Driver getDriver()
 */
class Element extends NodeElement
{
    use AssertTrait, SpinTrait;

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

        $this->init();
    }

    /**
     * Initialize element
     */
    protected function init()
    {
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param string $key
     * @return string|array|null
     */
    public function getOption($key)
    {
        if (!isset($this->options[$key])) {
            return null;
        }

        return $this->options[$key];
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
     * @param string $text label text
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
     * @return bool
     */
    public function isIsset()
    {
        return 0 !== count($this->getDriver()->find($this->getXpath()));
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
     *
     * @return Element[]
     */
    public function getElements($name)
    {
        return $this->elementFactory->findAllElements($name, $this);
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
     * {@inheritdoc}
     */
    public function click()
    {
        try {
            parent::click();
        } catch (NoSuchElement | ElementNotVisible $e) {
            $isClicked = $this->spin(function () {
                if ($this->isVisible()) {
                    parent::click();
                    return true;
                }
                return false;
            }, 3);

            if (!$isClicked) {
                throw $e;
            }
        }
    }

    /**
     * Click on button or link
     *
     * @param string $button
     */
    public function clickOrPress($button)
    {
        if ($this->hasButton($button)) {
            $this->pressButton($button);
        } else {
            $this->clickLink($button);
        }
    }

    /**
     * Executes JS code that force click on element
     */
    public function clickForce()
    {
        $this->getDriver()->executeJsOnXpath($this->getXpath(), '{{ELEMENT}}.click()');
    }

    /**
     * @return self
     */
    protected function getPage()
    {
        return $this->elementFactory->wrapElement(
            'Page',
            $this->session->getPage()
        );
    }

    /**
     * @return string
     */
    protected function getName()
    {
        return preg_replace('/^.*\\\(.*?)$/', '$1', get_class($this));
    }
}
