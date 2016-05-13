<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

interface OroElementFactoryAware
{
    /**
     * @param OroElementFactory $elementFactory
     *
     * @return null
     */
    public function setElementFactory(OroElementFactory $elementFactory);
}
