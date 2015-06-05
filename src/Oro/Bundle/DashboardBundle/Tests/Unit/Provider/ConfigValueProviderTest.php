<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueProvider;
use Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Provider\TestConverter;

class ConfigValueProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigValueProvider */
    protected $provider;

    public function setUp()
    {
        $converter = new TestConverter();

        $this->provider = new ConfigValueProvider();
        $this->provider->addConverter('test_form_type', $converter);
    }

    public function testGetConvertedValueUnsupported()
    {
        $value = 'test';
        $this->assertSame($value, $this->provider->getConvertedValue([], 'unsupported', $value));
    }

    public function testGetConvertedValue()
    {
        $value = 'test value';
        $this->assertSame('test value', $this->provider->getConvertedValue([], 'test_form_type', $value));
    }

    public function testGetViewValueUnsupported()
    {
        $value = 'test';
        $this->assertSame($value, $this->provider->getViewValue('unsupported', $value));
    }

    public function testGetViewValue()
    {
        $value = 'test';
        $this->assertSame('test view value', $this->provider->getViewValue('test_form_type', $value));
    }

    public function testGetFormValue()
    {
        $value          = 'test';
        $convertedValue = 'converted';
        $this->assertSame(
            $convertedValue,
            $this->provider->getFormValue(
                'test_form_type',
                ['value' => $convertedValue],
                $value
            )
        );
        $this->assertSame($value, $this->provider->getFormValue('unsupported', [], $value));
    }
}
