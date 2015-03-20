<?php

namespace Oro\Bundle\TrackingBundle\Processor;

use DeviceDetector\DeviceDetector;

class DeviceDetectorFactory
{
    protected static $deviceDetectorInstances = array();

    /**
     * Returns a Singleton instance of DeviceDetector for the given user agent
     *
     * @param string $userAgent
     * @return DeviceDetector
     */
    public static function getInstance($userAgent)
    {
        if (array_key_exists($userAgent, self::$deviceDetectorInstances)) {
            return self::$deviceDetectorInstances[$userAgent];
        }

        $deviceDetector = new DeviceDetector($userAgent);
        $deviceDetector->parse();

        self::$deviceDetectorInstances[$userAgent] = $deviceDetector;

        return $deviceDetector;
    }
}
