<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Environment;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverClassesProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

class TestEntityNameResolverClassesProvider implements TestEntityNameResolverClassesProviderInterface
{
    private const REASON = 'dataaudit';

    private TestEntityNameResolverClassesProviderInterface $innerProvider;
    private ConfigManager $configManager;
    private ManagerRegistry $doctrine;

    public function __construct(
        TestEntityNameResolverClassesProviderInterface $innerProvider,
        ConfigManager $configManager,
        ManagerRegistry $doctrine
    ) {
        $this->innerProvider = $innerProvider;
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getEntityClasses(): array
    {
        $foundEntityClasses = [];
        $entityConfigs = $this->configManager->getConfigs('dataaudit');
        foreach ($entityConfigs as $entityConfig) {
            $entityClass = $entityConfig->getId()->getClassName();
            if (!$this->isAuditableEntity($entityClass, $entityConfig)) {
                continue;
            }
            $metadata = $this->getEntityMetadata($entityClass);
            $fieldConfigs = $this->configManager->getConfigs('dataaudit', $entityClass);
            foreach ($fieldConfigs as $fieldConfig) {
                if (!$fieldConfig->is('auditable')) {
                    continue;
                }
                $fieldName = $fieldConfig->getId()->getFieldName();
                if (!$metadata->hasAssociation($fieldName)) {
                    continue;
                }
                $targetEntityClass = $metadata->getAssociationTargetClass($fieldName);
                if (!$this->configManager->hasConfig($targetEntityClass)) {
                    continue;
                }
                $targetEntityConfig = $this->configManager->getEntityConfig('dataaudit', $targetEntityClass);
                if (!$this->isAuditableEntity($targetEntityClass, $targetEntityConfig)) {
                    continue;
                }
                if (!isset($foundEntityClasses[$entityClass])) {
                    $foundEntityClasses[$entityClass] = true;
                }
            }
        }

        $entityClasses = $this->innerProvider->getEntityClasses();
        foreach ($foundEntityClasses as $entityClass => $val) {
            if (!isset($entityClasses[$entityClass])) {
                $entityClasses[$entityClass] = [self::REASON];
            } elseif (!\in_array(self::REASON, $entityClasses[$entityClass], true)) {
                $entityClasses[$entityClass][] = self::REASON;
            }
        }

        return $entityClasses;
    }

    private function isAuditableEntity(string $entityClass, ConfigInterface $entityConfig): bool
    {
        if (str_starts_with($entityClass, ExtendClassLoadingUtils::getEntityNamespace())) {
            return false;
        }
        if (is_a($entityClass, TestFrameworkEntityInterface::class, true)) {
            return false;
        }
        if (!$entityConfig->is('auditable')) {
            return false;
        }

        return true;
    }

    private function getEntityMetadata(string $entityClass): ClassMetadata
    {
        return $this->doctrine->getManagerForClass($entityClass)->getClassMetadata($entityClass);
    }
}
