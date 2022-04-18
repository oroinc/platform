<?php

namespace Oro\Bundle\PlatformBundle\Profiler;

/**
 * Provides symfony profiler configuration from the Cookie
 */
class ProfilerConfig
{
    public const ALWAYS_ENABLED_COLLECTORS = [
        'request',
        'time',
        'memory',
        'dump',
        'exception',
        'ajax',
        'collectors_toggle',
        'oro_platform',
        'config',
    ];

    public const ENABLED_COLLECTORS_COOKIE = 'sf_toolbar_enabled_collectors';

    private const TRACK_CONTAINER_COOKIE = 'sf_toolbar_track_container';

    private static ?array $enabledCollectors = null;

    public static function isCollectorEnabled(string $name): bool
    {
        if (in_array($name, self::ALWAYS_ENABLED_COLLECTORS)) {
            return true;
        }

        if (null === self::$enabledCollectors && array_key_exists(self::ENABLED_COLLECTORS_COOKIE, $_COOKIE)) {
            $cookie = $_COOKIE[self::ENABLED_COLLECTORS_COOKIE];
            self::$enabledCollectors = explode('~', $cookie);
        }

        if (null === self::$enabledCollectors) {
            return true;
        }

        return in_array($name, self::$enabledCollectors);
    }

    public static function trackContainerChanges(): bool
    {
        return !array_key_exists(self::TRACK_CONTAINER_COOKIE, $_COOKIE) ||
            $_COOKIE[self::TRACK_CONTAINER_COOKIE] === 'true';
    }
}
