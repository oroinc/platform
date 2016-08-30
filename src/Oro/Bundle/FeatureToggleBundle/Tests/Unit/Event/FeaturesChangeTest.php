<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Event;

use Oro\Bundle\FeatureToggleBundle\Event\FeaturesChange;

class FeaturesChangeTest extends \PHPUnit_Framework_TestCase
{
    public function testGetChangeSet()
    {
        $changeSet = ['feature1' => true, 'feature2' => false];
        $event = new FeaturesChange($changeSet);
        $this->assertEquals($changeSet, $event->getChangeSet());
    }
}
