<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TimezoneTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testFormTypeWithoutCache()
    {
        $type = new TimezoneType();
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->assertEquals(ChoiceType::class, $type->getParent(), 'Unexpected parent');
        $this->assertEquals('oro_locale_timezone', $type->getName(), 'Unexpected form type name');
        $type->configureOptions($resolver);
    }

    /**
     * @depends testFormTypeWithoutCache
     */
    public function testGetTimezonesData()
    {
        $timezones = TimezoneType::getTimezones();
        $this->assertIsArray($timezones);
        $this->assertNotEmpty($timezones);
        $this->assertArrayHasKey('UTC', $timezones);
        $this->assertEquals('(UTC +00:00) Other/UTC', $timezones['UTC']);
    }

    /**
     * @depends testGetTimezonesData
     */
    public function testFormTypeWithFilledCache()
    {
        $timezones = ['Test' => '(UTC +0) Test'];

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('timezones')
            ->willReturn($timezones);

        $type = new TimezoneType($cache);
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'choices' => array_flip($timezones),
            ]);
        $type->configureOptions($resolver);
    }

    /**
     * @depends testGetTimezonesData
     */
    public function testFormTypeWithEmptyCache()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('timezones')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $type = new TimezoneType($cache);
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $type->configureOptions($resolver);
    }

    public function testGetTimezones()
    {
        $timezones = TimezoneType::getTimezonesData();
        $this->assertIsArray($timezones);
        $this->assertNotEmpty($timezones);
        $this->assertArrayHasKey('offset', $timezones[0]);
        $this->assertArrayHasKey('timezone_id', $timezones[0]);
        $this->assertLessThan($timezones[count($timezones) - 1]['offset'], $timezones[0]['offset']);
    }
}
