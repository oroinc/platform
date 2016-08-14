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
     * @param Session $session
     * @param OroElementFactory $elementFactory
     * @param array|string $selector
     */
    public function __construct(
        Session $session,
        OroElementFactory $elementFactory,
        $selector = ['type' => 'xpath', 'locator' => '//']
    ) {
        parent::__construct($this->getSelectorAsXpath($session->getSelectorsHandler(), $selector), $session);

        $this->elementFactory = $elementFactory;
        $this->session = $session;
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
     * @return NodeElement|null
     */
    public function findLabel($text)
    {
        return $this->findContains('label', $text);
    }
    /**
     * @param $locator
     * @param $text
     * @return Element|null
     */
    public function findContains($locator, $text)
    {
        $variants = [
            $text,
            ucfirst($text),
            ucfirst(strtolower($text)),
            strtolower($text),
            strtoupper($text),
        ];

        $selector = implode(',', array_map(function ($variant) use ($locator) {
            return sprintf('%s:contains("%s")', $locator, $variant);
        }, $variants));

        $element = $this->find('css', $selector);

        if (null === $element) {
            return null;
        }

        return new self($this->session, $this->elementFactory, ['type' => 'xpath', 'locator' => $element->getXpath()]);
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

    /**
     * @param SelectorsHandler $selectorsHandler
     * @param array|string $selector
     *
     * @return string
     */
    private function getSelectorAsXpath(SelectorsHandler $selectorsHandler, $selector)
    {
        $selectorType = is_array($selector) ? $selector['type'] : 'css';
        $locator = is_array($selector) ? $selector['locator'] : $selector;

        return $selectorsHandler->selectorToXpath($selectorType, $locator);
    }
}
