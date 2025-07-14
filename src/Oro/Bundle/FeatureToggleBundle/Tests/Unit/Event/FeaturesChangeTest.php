<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Event;

use Oro\Bundle\FeatureToggleBundle\Event\FeaturesChange;
use PHPUnit\Framework\TestCase;

class FeaturesChangeTest extends TestCase
{
    public function testGetChangeSet(): void
    {
        $changeSet = ['feature1' => true, 'feature2' => false];
        $event = new FeaturesChange($changeSet);
        $this->assertEquals($changeSet, $event->getChangeSet());
    }
}
