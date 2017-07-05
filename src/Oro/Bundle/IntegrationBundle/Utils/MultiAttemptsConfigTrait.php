<?php

namespace Oro\Bundle\IntegrationBundle\Utils;

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
