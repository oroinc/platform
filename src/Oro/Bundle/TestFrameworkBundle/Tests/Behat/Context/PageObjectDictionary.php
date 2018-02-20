<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element as OroElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageFactory;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

trait PageObjectDictionary
{
    /**
     * @var OroElementFactory
     */
    protected $elementFactory;

    /**
     * @var OroPageFactory
     */
    protected $pageFactory;

    /**
     * {@inheritdoc}
     */
    public function setElementFactory(OroElementFactory $elementFactory)
    {
        $this->elementFactory = $elementFactory;
    }

    public function setPageFactory(OroPageFactory $pageFactory)
    {
        $this->pageFactory = $pageFactory;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasElement($name)
    {
        return $this->elementFactory->hasElement($name);
    }

    /**
     * @param string $name
     * @param NodeElement $context
     * @return Element
     */
    public function createElement($name, NodeElement $context = null)
    {
        return $this->elementFactory->createElement($name, $context);
    }

    /**
     * @param string $name
     * @param NodeElement|null $context
     * @return Element[]
     */
    public function findAllElements($name, NodeElement $context = null)
    {
        return $this->elementFactory->findAllElements($name, $context);
    }

    /**
     * @param string $name Element name
     * @param string $text Text that contains in element node
     * @param OroElement $context
     *
     * @return OroElement
     */
    public function findElementContains($name, $text, OroElement $context = null)
    {
        return $this->elementFactory->findElementContains($name, $text, $context);
    }

    /**
     * @return Page|OroElement
     */
    public function getPage($name = null)
    {
        if (null === $name) {
            return $this->elementFactory->getPage();
        }

        return $this->pageFactory->getPage($name);
    }

    /**
     * @param string $elementName
     * @param NodeElement $context
     *
     * @return bool
     */
    public function isElementVisible($elementName, NodeElement $context = null)
    {
        if ($this->hasElement($elementName)) {
            $element = $this->createElement($elementName, $context);
            if ($element->isValid() && $element->isVisible()) {
                return true;
            }
        }

        return false;
    }
}
