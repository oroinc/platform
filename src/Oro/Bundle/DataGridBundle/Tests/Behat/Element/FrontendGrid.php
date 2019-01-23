<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

class FrontendGrid extends Grid
{
    /**
     * {@inheritdoc}
     */
    public function getMappedChildElementName($name)
    {
        return $name;
    }
}
