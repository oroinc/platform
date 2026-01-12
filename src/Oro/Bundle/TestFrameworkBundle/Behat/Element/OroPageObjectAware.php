<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

/**
 * Defines the contract for classes that need to be aware of page and element factories.
 *
 * Classes implementing this interface can be injected with {@see OroElementFactory} and {@see OroPageFactory}
 * to access page objects and UI elements during test execution.
 */
interface OroPageObjectAware
{
    /**
     * @param OroElementFactory $elementFactory
     *
     * @return void
     */
    public function setElementFactory(OroElementFactory $elementFactory);

    /**
     * @param OroPageFactory $elementFactory
     *
     * @return void
     */
    public function setPageFactory(OroPageFactory $elementFactory);
}
