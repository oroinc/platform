<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Session;

class Element extends NodeElement
{
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
    public function __construct(Session $session, OroElementFactory $elementFactory, $selector = ['xpath' => '//'])
    {
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
