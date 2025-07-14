<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use PHPUnit\Framework\TestCase;

class AccessLevelTest extends TestCase
{
    public function testConstantValues(): void
    {
        self::assertEquals(-1, AccessLevel::UNKNOWN);
        self::assertEquals(0, AccessLevel::NONE_LEVEL);
        self::assertGreaterThan(AccessLevel::NONE_LEVEL, AccessLevel::BASIC_LEVEL);
        self::assertGreaterThan(AccessLevel::BASIC_LEVEL, AccessLevel::LOCAL_LEVEL);
        self::assertGreaterThan(AccessLevel::LOCAL_LEVEL, AccessLevel::DEEP_LEVEL);
        self::assertGreaterThan(AccessLevel::DEEP_LEVEL, AccessLevel::GLOBAL_LEVEL);
        self::assertGreaterThan(AccessLevel::GLOBAL_LEVEL, AccessLevel::SYSTEM_LEVEL);
    }

    public function testAllAccessLevelNames(): void
    {
        self::assertEquals(['BASIC', 'LOCAL', 'DEEP', 'GLOBAL', 'SYSTEM'], AccessLevel::$allAccessLevelNames);
    }

    /**
     * @dataProvider getAccessLevelNameDataProvider
     */
    public function testGetAccessLevelName(int $accessLevel, ?string $name): void
    {
        self::assertSame($name, AccessLevel::getAccessLevelName($accessLevel));
    }

    public static function getAccessLevelNameDataProvider(): array
    {
        return [
            [AccessLevel::UNKNOWN, null],
            [AccessLevel::NONE_LEVEL, null],
            [AccessLevel::BASIC_LEVEL, 'BASIC'],
            [AccessLevel::LOCAL_LEVEL, 'LOCAL'],
            [AccessLevel::DEEP_LEVEL, 'DEEP'],
            [AccessLevel::GLOBAL_LEVEL, 'GLOBAL'],
            [AccessLevel::SYSTEM_LEVEL, 'SYSTEM']
        ];
    }

    public function testGetAccessLevelNamesWithDefaultArguments(): void
    {
        self::assertSame(
            [
                AccessLevel::NONE_LEVEL   => 'NONE',
                AccessLevel::BASIC_LEVEL  => 'BASIC',
                AccessLevel::LOCAL_LEVEL  => 'LOCAL',
                AccessLevel::DEEP_LEVEL   => 'DEEP',
                AccessLevel::GLOBAL_LEVEL => 'GLOBAL',
                AccessLevel::SYSTEM_LEVEL => 'SYSTEM'
            ],
            AccessLevel::getAccessLevelNames()
        );
    }

    /**
     * @dataProvider getAccessLevelNamesDataProvider
     */
    public function testGetAccessLevelNames(int $minLevel, int $maxLevel, array $excludeLevels, array $names): void
    {
        self::assertSame($names, AccessLevel::getAccessLevelNames($minLevel, $maxLevel, $excludeLevels));
    }

    public static function getAccessLevelNamesDataProvider(): array
    {
        return [
            [
                AccessLevel::BASIC_LEVEL,
                AccessLevel::SYSTEM_LEVEL,
                [],
                [
                    AccessLevel::NONE_LEVEL   => 'NONE',
                    AccessLevel::BASIC_LEVEL  => 'BASIC',
                    AccessLevel::LOCAL_LEVEL  => 'LOCAL',
                    AccessLevel::DEEP_LEVEL   => 'DEEP',
                    AccessLevel::GLOBAL_LEVEL => 'GLOBAL',
                    AccessLevel::SYSTEM_LEVEL => 'SYSTEM'
                ]
            ],
            [
                AccessLevel::LOCAL_LEVEL,
                AccessLevel::GLOBAL_LEVEL,
                [],
                [
                    AccessLevel::NONE_LEVEL   => 'NONE',
                    AccessLevel::LOCAL_LEVEL  => 'LOCAL',
                    AccessLevel::DEEP_LEVEL   => 'DEEP',
                    AccessLevel::GLOBAL_LEVEL => 'GLOBAL'
                ]
            ],
            [
                AccessLevel::LOCAL_LEVEL,
                AccessLevel::LOCAL_LEVEL,
                [],
                [
                    AccessLevel::NONE_LEVEL  => 'NONE',
                    AccessLevel::LOCAL_LEVEL => 'LOCAL'
                ]
            ],
            [
                AccessLevel::BASIC_LEVEL,
                AccessLevel::SYSTEM_LEVEL,
                [AccessLevel::GLOBAL_LEVEL],
                [
                    AccessLevel::NONE_LEVEL   => 'NONE',
                    AccessLevel::BASIC_LEVEL  => 'BASIC',
                    AccessLevel::LOCAL_LEVEL  => 'LOCAL',
                    AccessLevel::DEEP_LEVEL   => 'DEEP',
                    AccessLevel::SYSTEM_LEVEL => 'SYSTEM'
                ]
            ]
        ];
    }
}
