<?php

namespace Oro\Bundle\ThemeBundle\Fallback\Provider;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackFieldConfigurationMissingException;
use Oro\Bundle\EntityBundle\Fallback\Provider\AbstractEntityFallbackProvider;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;

/**
 * Entity fallback provider which fetches data from theme configuration.
 */
class ThemeConfigurationFallbackProvider extends AbstractEntityFallbackProvider
{
    public const CONFIG_NAME_KEY = 'configName';
    public const FALLBACK_ID = 'themeConfiguration';

    public function __construct(
        protected ThemeConfigurationProvider $themeConfigurationProvider
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getFallbackLabel(): string
    {
        return 'oro.theme.themeconfiguration.fallback.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getFallbackEntityClass(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     * @throws FallbackFieldConfigurationMissingException
     */
    public function getFallbackHolderEntity($object, $objectFieldName): mixed
    {
        $fallbackConfig = $this->getEntityConfig($object, $objectFieldName);

        if (!array_key_exists(EntityFieldFallbackValue::FALLBACK_LIST, $fallbackConfig)) {
            throw new FallbackFieldConfigurationMissingException(
                sprintf(
                    "You must define the fallback configuration '%s' for the class '%s' field '%s'",
                    EntityFieldFallbackValue::FALLBACK_LIST,
                    get_class($object),
                    $objectFieldName
                )
            );
        }

        $fallbackListConfig = $fallbackConfig[EntityFieldFallbackValue::FALLBACK_LIST];
        if (!array_key_exists(self::FALLBACK_ID, $fallbackListConfig)) {
            throw new FallbackFieldConfigurationMissingException(
                sprintf(
                    "You must define the fallback id configuration '%s' for the class '%s' field '%s'",
                    self::FALLBACK_ID,
                    get_class($object),
                    $objectFieldName
                )
            );
        }

        $themeConfigurationConfig = $fallbackListConfig[self::FALLBACK_ID];
        if (!array_key_exists(self::CONFIG_NAME_KEY, $themeConfigurationConfig)) {
            throw new FallbackFieldConfigurationMissingException(
                sprintf(
                    "You must define the '%s' fallback option for entity '%s' field '%s', fallback id '%s'",
                    self::CONFIG_NAME_KEY,
                    get_class($object),
                    $objectFieldName,
                    self::FALLBACK_ID
                )
            );
        }

        return $this->themeConfigurationProvider->getThemeConfigurationOption(
            $themeConfigurationConfig[self::CONFIG_NAME_KEY]
        );
    }
}
