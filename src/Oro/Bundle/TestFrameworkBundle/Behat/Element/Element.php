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
     * Finds label with specified locator.
     *
     * @param string $locator label text
     *
     * @return NodeElement|null
     */
    public function findLabel($locator)
    {
        $labelSelector = sprintf("label:contains('%s')", $locator);

        return $this->find('css', $labelSelector);
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
