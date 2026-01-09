<?php

namespace Oro\Bundle\IntegrationBundle\Utils;

/**
 * Provides configuration management for multi-attempt retry logic in REST clients.
 *
 * This trait is used by client decorators to manage retry configuration, including
 * enabling/disabling retries and configuring sleep intervals between retry attempts.
 * It provides default configuration parameters and helper methods for accessing
 * retry-related settings.
 */
trait MultiAttemptsConfigTrait
{
    public static $multiAttemptsConfigKey = 'multipleAttempts';

    protected $multiAttemptsDefaultConfigurationParameters = [
        'enabled' => true,
        'sleepBetweenAttempt' => [5, 10, 20, 40]
    ];

    /**
     * @return array
     */
    public static function getMultiAttemptsDisabledConfig()
    {
        return [
            self::$multiAttemptsConfigKey => [
                'enabled' => false
            ]
        ];
    }

    /**
     * @param array $configuration
     *
     * @return array
     */
    protected function getSleepBetweenAttemptsParameter(array $configuration)
    {
        $sleepBetweenAttempts = $configuration['sleepBetweenAttempt'];
        if (is_array($sleepBetweenAttempts)) {
            return $sleepBetweenAttempts;
        }

        return [];
    }

    /**
     * @param array $configuration
     *
     * @return bool
     */
    protected function getMultiAttemptsEnabledParameter(array $configuration)
    {
        return (bool) $configuration['enabled'];
    }
}
