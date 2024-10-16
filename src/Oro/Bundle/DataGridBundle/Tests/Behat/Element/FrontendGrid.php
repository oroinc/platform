<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

class FrontendGrid extends Grid
{
    #[\Override]
    public function getMappedChildElementName($name)
    {
        return $name;
    }
}
