<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

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
