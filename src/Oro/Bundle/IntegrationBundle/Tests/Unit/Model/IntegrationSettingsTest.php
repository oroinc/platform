<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model;

use Oro\Bundle\IntegrationBundle\Model\IntegrationSettings;

class IntegrationSettingsTest extends \PHPUnit_Framework_TestCase
{
    const TEST_KEY    = 'keyComplex';
    const TEST_VALUE  = 'value';
    const TEST_VALUE2 = 'value2';

    /** @var array */
    protected $testSettings = [self::TEST_KEY => self::TEST_VALUE];

    public function testProcessEmptyConstruction()
    {
        $settings = new IntegrationSettings();
        $this->assertTrue($settings->isEmpty());
    }

    public function testProcessSettingsSetFromConstructor()
    {
        $settings = new IntegrationSettings($this->testSettings);
        $this->assertFalse($settings->isEmpty());
    }

    public function testPropertyAccess()
    {
        $settings = new IntegrationSettings($this->testSettings);

        $this->assertTrue($settings->hasKeyComplex());
        $this->assertEquals(self::TEST_VALUE, $settings->getKeyComplex());

        $this->assertFalse($settings->hasSomeKey());
        $settings->setSomeKey(self::TEST_VALUE2);
        $this->assertTrue($settings->hasSomeKey());
    }

    public function testSerialization()
    {
        $settings   = new IntegrationSettings($this->testSettings);
        $serialized = serialize($settings);

        $settings = unserialize($serialized);

        $this->assertFalse($settings->isEmpty());
        $this->assertTrue($settings->hasKeyComplex());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThrowsErrorsOnNotDefinedMethods()
    {
        $settings = new IntegrationSettings();
        $settings->testBadMethod();
    }
}
