<?php

namespace Oro\Bundle\UIBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Oro\Bundle\UIBundle\Tests\Behat\Element\BreadcrumbContainerElement;

class BreadcrumbContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Given /^(?:|I )follow "([^"]*)" breadcrumb$/
     *
     * @param string $breadcrumbText
     */
    public function iFollowBreadcrumb($breadcrumbText)
    {
        /** @var BreadcrumbContainerElement $breadcrumbContainer */
        $breadcrumbContainer = $this->elementFactory->createElement('Breadcrumb Container');
        $breadcrumb = $breadcrumbContainer->getBreadcrumb($breadcrumbText);
        $breadcrumb->click();
    }
}
