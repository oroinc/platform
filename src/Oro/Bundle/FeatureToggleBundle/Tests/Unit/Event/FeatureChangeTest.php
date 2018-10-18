<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Event;

use Oro\Bundle\FeatureToggleBundle\Event\FeatureChange;

class FeatureChangeTest extends \PHPUnit\Framework\TestCase
{
    public function testGetFeatureName()
    {
        $event = new FeatureChange('feature1', true);
        $this->assertEquals('feature1', $event->getFeatureName());
    }

    public function testIsFeatureState()
    {
        $event = new FeatureChange('feature1', true);
        $this->assertEquals(true, $event->isEnabled());
    }
}
