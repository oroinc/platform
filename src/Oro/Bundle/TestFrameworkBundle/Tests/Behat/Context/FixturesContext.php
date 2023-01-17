<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderDictionary;

class FixturesContext extends OroFeatureContext implements FixtureLoaderAwareInterface
{
    use FixtureLoaderDictionary;

    /**
     * Load entity with some data
     * Example:
     *   Given the following activity list:
     *     | subject      |
     *     | hello world! |
     *     | <sentence()> |
     *
     * @Given /^(?:the|there are|there is) following ([\w ]+):?$/
     */
    public function theFollowing($name, TableNode $table)
    {
        $this->fixtureLoader->loadTable($name, $table);
    }
}
