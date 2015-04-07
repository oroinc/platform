<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Provider;

use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent;
use Oro\Bundle\TrackingBundle\Provider\TrackingEventIdentificationProvider;
use Oro\Bundle\TrackingBundle\Tests\Unit\Fixture\TestProvider;

class TrackingEventIdentificationProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var TrackingEventIdentificationProvider */
    protected $provider;

    public function setUp()
    {
        $testIdentifier = new TestProvider();
        $this->provider = new TrackingEventIdentificationProvider();
        $this->provider->addProvider($testIdentifier);
    }

    public function testIdentify()
    {
        $this->assertEquals(
            'identity',
            $this->provider->identify(new TrackingVisit())->value
        );
    }

    public function testGetTargetIdentityEntities()
    {
        $this->assertEquals(
            ['\stdClassIdentity'],
            $this->provider->getTargetIdentityEntities()
        );
    }

    public function testGetEventTargetEntities()
    {
        $this->assertEquals(
            ['\stdClass'],
            $this->provider->getEventTargetEntities()
        );
    }

    public function testProcessEvent()
    {
        $this->assertEquals(
            'event',
            $this->provider->processEvent(new TrackingVisitEvent())[0]->value
        );
    }
}
