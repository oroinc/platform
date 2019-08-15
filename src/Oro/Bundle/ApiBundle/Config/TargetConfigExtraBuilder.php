<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * A helper class that can be used to build configuration extras
 * to get a configuration of an association target entity.
 */
class TargetConfigExtraBuilder
{
    /**
     * Builds configuration extras to get a configuration of the target entity for the given association.
     *
     * @param ConfigExtraInterface[] $parentConfigExtras
     * @param string|null            $associationPath
     *
     * @return ConfigExtraInterface[]
     */
    public static function buildConfigExtras(array $parentConfigExtras, ?string $associationPath): array
    {
        $result = [];
        foreach ($parentConfigExtras as $extra) {
            if ($extra instanceof ExpandRelatedEntitiesConfigExtra) {
                $extra = self::buildExpandRelatedEntitiesConfigExtra($extra, $associationPath);
            } elseif ($extra instanceof FiltersConfigExtra
                || $extra instanceof SortersConfigExtra
                || $extra instanceof RootPathConfigExtra
            ) {
                $extra = null;
            }
            if (null !== $extra) {
                $result[] = $extra;
            }
        }
        if ($associationPath) {
            $result[] = new RootPathConfigExtra($associationPath);
        }

        return $result;
    }

    /**
     * Builds configuration extras to get a configuration of the parent entity contains the given association.
     * Usually this method is used together with {@see normalizeParentConfig} to build a configuration
     * of the parent entity.
     *
     * Example of building a configuration of the parent entity:
     * <code>
     *  $parentConfigExtras = TargetConfigExtraBuilder::buildParentConfigExtras(
     *      $configExtras,
     *      $parentClassName,
     *      $associationName
     *  );
     *  $parentConfig = $this->configProvider
     *      ->getConfig($parentClassName, $version, $requestType, $parentConfigExtras)
     *      ->getDefinition();
     *  TargetConfigExtraBuilder::normalizeParentConfig($parentConfig, $associationName, $parentConfigExtras);
     * </code>
     *
     * @param ConfigExtraInterface[] $configExtras
     * @param string                 $parentClassName
     * @param string                 $associationName
     *
     * @return ConfigExtraInterface[]
     */
    public static function buildParentConfigExtras(
        array $configExtras,
        string $parentClassName,
        string $associationName
    ): array {
        $result = [];
        $hasExpandExtra = false;
        $hasCustomizeExtra = false;
        $hasTransformExtra = false;
        $fieldsExtraKey = null;
        foreach ($configExtras as $extra) {
            if ($extra instanceof ExpandRelatedEntitiesConfigExtra) {
                $extra = self::buildParentExpandRelatedEntitiesConfigExtra($extra, $associationName);
                $hasExpandExtra = true;
            } elseif ($extra instanceof FilterFieldsConfigExtra) {
                $fieldsExtraKey = \count($result);
            } elseif ($extra instanceof RootPathConfigExtra
                || $extra instanceof FilterIdentifierFieldsConfigExtra
            ) {
                $extra = null;
            } elseif ($extra instanceof CustomizeLoadedDataConfigExtra) {
                $hasCustomizeExtra = true;
            } elseif ($extra instanceof DataTransformersConfigExtra) {
                $hasTransformExtra = true;
            }
            if (null !== $extra) {
                $result[] = $extra;
            }
        }
        if (!$hasExpandExtra) {
            $fieldsExtra = new FilterFieldsConfigExtra([$parentClassName => [$associationName]]);
            if (null === $fieldsExtraKey) {
                $configExtras[] = $fieldsExtra;
            } else {
                $configExtras[$fieldsExtraKey] = $fieldsExtra;
            }
        }
        if (!$hasCustomizeExtra) {
            $result[] = new CustomizeLoadedDataConfigExtra();
        }
        if (!$hasTransformExtra) {
            $result[] = new DataTransformersConfigExtra();
        }

        return $result;
    }

    /**
     * Makes sure that the configuration of the given parent entity
     * is ready to be used to load the given association data.
     *
     * @param EntityDefinitionConfig      $parentConfig
     * @param string                      $associationName
     * @param ConfigExtraInterface[]|null $parentConfigExtras
     */
    public static function normalizeParentConfig(
        EntityDefinitionConfig $parentConfig,
        string $associationName,
        array $parentConfigExtras = null
    ): void {
        $isNormalizationRequired = true;
        if (null !== $parentConfigExtras) {
            $isNormalizationRequired = false;
            foreach ($parentConfigExtras as $extra) {
                if ($extra instanceof ExpandRelatedEntitiesConfigExtra) {
                    $isNormalizationRequired = true;
                    break;
                }
            }
        }
        if (!$isNormalizationRequired) {
            return;
        }

        $fields = $parentConfig->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                if ($fieldName === $associationName) {
                    $field->setExcluded(false);
                }
            } elseif ($fieldName !== $associationName) {
                $field->setExcluded();
            }
        }
    }

    /**
     * @param ExpandRelatedEntitiesConfigExtra $extra
     * @param string|null                      $associationPath
     *
     * @return ExpandRelatedEntitiesConfigExtra|null
     */
    private static function buildExpandRelatedEntitiesConfigExtra(
        ExpandRelatedEntitiesConfigExtra $extra,
        ?string $associationPath
    ): ?ExpandRelatedEntitiesConfigExtra {
        if (!$associationPath) {
            return $extra;
        }

        $expandedEntities = [];
        $pathPrefix = $associationPath . ConfigUtil::PATH_DELIMITER;
        $pathPrefixLength = \strlen($pathPrefix);
        foreach ($extra->getExpandedEntities() as $path) {
            if (0 === \strpos($path, $pathPrefix)) {
                $expandedEntities[] = \substr($path, $pathPrefixLength);
            }
        }

        return $expandedEntities
            ? new ExpandRelatedEntitiesConfigExtra($expandedEntities)
            : null;
    }

    /**
     * @param ExpandRelatedEntitiesConfigExtra $extra
     * @param string                           $associationName
     *
     * @return ExpandRelatedEntitiesConfigExtra
     */
    private static function buildParentExpandRelatedEntitiesConfigExtra(
        ExpandRelatedEntitiesConfigExtra $extra,
        string $associationName
    ): ExpandRelatedEntitiesConfigExtra {
        $expandedEntities = [];
        foreach ($extra->getExpandedEntities() as $path) {
            $expandedEntities[] = $associationName . ConfigUtil::PATH_DELIMITER . $path;
        }

        return new ExpandRelatedEntitiesConfigExtra($expandedEntities);
    }
}
