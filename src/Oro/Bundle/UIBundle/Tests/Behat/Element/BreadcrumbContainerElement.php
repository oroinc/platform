<?php

namespace Oro\Bundle\UIBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class BreadcrumbContainerElement extends Element
{
    /**
     * @param string $text The breadcrumb text
     *
     * @return Element
     */
    public function getBreadcrumb($text)
    {
        $breadcrumb = $this->elementFactory->findElementContainsByXPath('Breadcrumb', $text, false, $this);
        if (null !== $breadcrumb) {
            return $breadcrumb;
        }

        self::fail(sprintf('Can\'t find breadcrumb with "%s" text', $text));
    }
}
