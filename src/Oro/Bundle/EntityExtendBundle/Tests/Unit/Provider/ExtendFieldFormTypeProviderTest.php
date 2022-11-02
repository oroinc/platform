<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormTypeProvider;

class ExtendFieldFormTypeProviderTest extends \PHPUnit\Framework\TestCase
{
    private ExtendFieldFormTypeProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ExtendFieldFormTypeProvider();
    }

    public function testGetFormTypeReturnsEmptyStringWhenNoMatch(): void
    {
        self::assertEquals('', $this->provider->getFormType('missing_type'));
    }

    public function testGetFormOptionsReturnsEmptyArrayWhenNoMatch(): void
    {
        self::assertEquals([], $this->provider->getFormOptions('missing_type'));
    }

    public function testGetFormTypeReturnsTypeWhenMatched(): void
    {
        $fieldType = 'sample_type';
        $this->provider->addExtendTypeMapping($fieldType, 'text');

        self::assertEquals('text', $this->provider->getFormType($fieldType));
        self::assertEquals([], $this->provider->getFormOptions($fieldType));
    }

    public function testGetFormOptionsReturnsTypeWhenMatched(): void
    {
        $fieldType = 'sample_type';
        $formOptions = ['sample_key' => 'sample_value'];
        $this->provider->addExtendTypeMapping($fieldType, 'text', $formOptions);

        self::assertEquals('text', $this->provider->getFormType($fieldType));
        self::assertEquals($formOptions, $this->provider->getFormOptions($fieldType));
    }
}
