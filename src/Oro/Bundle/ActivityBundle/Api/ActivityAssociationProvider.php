<?php

namespace Oro\Bundle\ActivityBundle\Api;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides information about associations of activity entities with other entities.
 */
class ActivityAssociationProvider implements ResetInterface
{
    private const KEY_DELIMITER = '|';

    private array $activityAssociationNames;
    private DoctrineHelper $doctrineHelper;
    private ConfigManager $configManager;
    private ActivityManager $activityManager;
    private ResourcesProvider $resourcesProvider;
    private ?EnglishInflector $inflector = null;
    private array $activityAssociations = [];
    private array $activityEntities = [];

    public function __construct(
        array $activityAssociationNames,
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        ActivityManager $activityManager,
        ResourcesProvider $resourcesProvider
    ) {
        $this->activityAssociationNames = $activityAssociationNames;
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->activityManager = $activityManager;
        $this->resourcesProvider = $resourcesProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->activityAssociations = [];
        $this->activityEntities = [];
    }

    public function isActivityEntity(string $entityClass): bool
    {
        if (isset($this->activityEntities[$entityClass])) {
            return $this->activityEntities[$entityClass];
        }

        $isActivityEntity = is_a($entityClass, ActivityInterface::class, true)
            && $this->doctrineHelper->isManageableEntityClass($entityClass)
            && $this->configManager->hasConfig($entityClass);
        $this->activityEntities[$entityClass] = $isActivityEntity;

        return $isActivityEntity;
    }

    public function getActivityAssociations(
        string $entityClass,
        string $version,
        RequestType $requestType
    ): array {
        $cacheKey = (string)$requestType . self::KEY_DELIMITER . $version . self::KEY_DELIMITER . $entityClass;
        if (isset($this->activityAssociations[$cacheKey])) {
            return $this->activityAssociations[$cacheKey];
        }

        $result = [];
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)
            && $this->configManager->hasConfig($entityClass)
        ) {
            $activityAssociations = $this->activityManager->getActivityAssociations($entityClass);
            foreach ($activityAssociations as $activityAssociation) {
                $targetEntityClass = $activityAssociation['className'];
                if ($this->resourcesProvider->isResourceAccessible($targetEntityClass, $version, $requestType)) {
                    $result[$this->getAssociationName($targetEntityClass)] = [
                        'className'       => $targetEntityClass,
                        'associationName' => $activityAssociation['associationName']
                    ];
                }
            }
        }
        $this->activityAssociations[$cacheKey] = $result;

        return $result;
    }

    public function getActivityTargetClasses(
        string $activityEntityClass,
        string $version,
        RequestType $requestType
    ): array {
        $result = [];
        $activityTargets = $this->activityManager->getActivityTargets($activityEntityClass);
        foreach ($activityTargets as $entityClass => $fieldName) {
            if ($this->resourcesProvider->isResourceAccessible($entityClass, $version, $requestType)) {
                $result[] = $entityClass;
            }
        }
        if ($result) {
            sort($result);
        }

        return $result;
    }

    private function getAssociationName(string $entityClass): string
    {
        if (isset($this->activityAssociationNames[$entityClass])) {
            return $this->activityAssociationNames[$entityClass];
        }

        $names = $this->getInflector()->pluralize($this->getShortClassName($entityClass));

        return 'activity' . reset($names);
    }

    private function getShortClassName(string $className): string
    {
        $lastDelimiter = strrpos($className, '\\');

        return false === $lastDelimiter
            ? $className
            : substr($className, $lastDelimiter + 1);
    }

    private function getInflector(): EnglishInflector
    {
        if (null === $this->inflector) {
            $this->inflector = new EnglishInflector();
        }

        return $this->inflector;
    }
}
