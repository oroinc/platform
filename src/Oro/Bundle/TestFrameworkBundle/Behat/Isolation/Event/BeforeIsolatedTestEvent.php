<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;

final class BeforeIsolatedTestEvent implements TestIsolationEvent
{
    /**
     * @var ScenarioNode|FeatureNode
     */
    protected $test;

    public function __construct($test)
    {
        $this->test = $test;
    }

    public function getTags()
    {
        return $this->test->getTags();
    }
}
