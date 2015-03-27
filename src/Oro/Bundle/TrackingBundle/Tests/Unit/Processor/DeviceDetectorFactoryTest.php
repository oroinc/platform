<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Processor;

use Oro\Bundle\TrackingBundle\Processor\DeviceDetectorFactory;

class DeviceDetectorFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testDeviceDetector()
    {
        $testUA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/600.4.10 Version/8.0.4 Safari/600.4.10';
        $detector = DeviceDetectorFactory::getInstance($testUA);
        $this->assertEquals('Safari', $detector->getClient()['name']);
        $this->assertSame($detector, DeviceDetectorFactory::getInstance($testUA));
        DeviceDetectorFactory::clearInstances();
        $this->assertNotSame($detector, DeviceDetectorFactory::getInstance($testUA));
    }
}
