<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Event;

use Oro\Bundle\FeatureToggleBundle\Event\FeatureChange;
use PHPUnit\Framework\TestCase;

class FeatureChangeTest extends TestCase
{
    public function testGetFeatureName(): void
    {
        $event = new FeatureChange('feature1', true);
        $this->assertEquals('feature1', $event->getFeatureName());
    }

    public function testIsFeatureState(): void
    {
        $event = new FeatureChange('feature1', true);
        $this->assertEquals(true, $event->isEnabled());
    }
}
