<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Symfony\Component\Translation\TranslatorInterface;

class EnumTranslationCacheTest extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME = 'FooBar';
    const LOCALE = 'en';

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var EnumTranslationCache|\PHPUnit\Framework\MockObject\MockObject */
    private $enumTranslationCache;

    public function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn(self::LOCALE);

        $this->cache = $this->createMock(Cache::class);

        $this->enumTranslationCache = new EnumTranslationCache($this->translator, $this->cache);
    }

    /**
     * @param bool $isContains
     * @param bool $expected
     *
     * @dataProvider getDataForContains
     */
    public function testContains(bool $isContains, bool $expected)
    {
        $this->cache->expects($this->once())
            ->method('contains')
            ->with($this->getKey())
            ->willReturn($isContains);

        $this->assertEquals($expected, $this->enumTranslationCache->contains(self::CLASS_NAME));
    }

    /**
     * @return array
     */
    public function getDataForContains(): array
    {
        return [
            'not contains' => [
                'isContains' => false,
                'expected' => false
            ],
            'contains' => [
                'isContains' => true,
                'expected' => true
            ]
        ];
    }

    /**
     * @param bool $isContains
     * @param array $values
     *
     * @dataProvider getDataForFetch
     */
    public function testFetch(bool $isContains, array $values)
    {
        $key = $this->getKey();

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($key)
            ->willReturn($isContains ? $values : false);

        $this->assertEquals($values, $this->enumTranslationCache->fetch(self::CLASS_NAME));
    }

    /**
     * @return array
     */
    public function getDataForFetch(): array
    {
        return [
            'not contains' => [
                'isContains' => false,
                'values' => []
            ],
            'contains empty' => [
                'isContains' => true,
                'values' => []
            ],
            'contains values' => [
                'isContains' => true,
                'values' => [
                    ['value' => 1],
                    ['value' => 2]
                ]
            ]
        ];
    }

    public function testSave()
    {
        $key = $this->getKey();
        $values = [
            ['value' => 1],
            ['value' => 2]
        ];

        $this->cache->expects($this->once())
            ->method('save')
            ->with($key);

        $this->enumTranslationCache->save(self::CLASS_NAME, $values);
    }

    public function testInvalidate()
    {
        $key = $this->getKey();

        $this->cache->expects($this->once())
            ->method('delete')
            ->with($key);

        $this->enumTranslationCache->invalidate(self::CLASS_NAME);
    }

    /**
     * @return string
     */
    private function getKey(): string
    {
        return sprintf('%s|%s', self::CLASS_NAME, self::LOCALE);
    }
}
