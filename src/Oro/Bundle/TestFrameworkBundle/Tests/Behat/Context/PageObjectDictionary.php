<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Mink\Element\DocumentElement;
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
     * @return OroElement
     */
    public function createElement($name)
    {
        return $this->elementFactory->createElement($name);
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
     * @return Page|DocumentElement
     */
    public function getPage($name = null)
    {
        if (null === $name) {
            return $this->elementFactory->getPage();
        }

        return $this->pageFactory->getPage($name);
    }
}
