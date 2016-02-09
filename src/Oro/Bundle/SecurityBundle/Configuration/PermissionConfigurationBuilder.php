<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;
use Oro\Bundle\SecurityBundle\Exception\MissedRequiredOptionException;

class PermissionConfigurationBuilder
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var array
     */
    private $processedEntities;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param array $configuration
     * @return Permission[]
     */
    public function buildPermissions(array $configuration)
    {
        $permissions = [];
        $this->processedEntities = [];
        foreach ($configuration as $name => $permissionConfiguration) {
            $permissions[] = $this->buildPermission($name, $permissionConfiguration);
        }
        $this->processedEntities = [];

        return $permissions;
    }

    /**
     * @param string $name
     * @param array $configuration
     * @return Permission
     */
    protected function buildPermission($name, array $configuration)
    {
        $this->assertConfigurationOptions($configuration, ['label']);

        $permission = new Permission();
        $permission
            ->setName($name)
            ->setLabel($configuration['label'])
            ->setApplyToAll($this->getConfigurationOption($configuration, 'apply_to_all', true))
            ->setGroupNames($this->getConfigurationOption($configuration, 'group_names', []))
            ->setExcludeEntities(
                $this->buildPermissionEntities($this->getConfigurationOption($configuration, 'exclude_entities', []))
            )
            ->setApplyToEntities(
                $this->buildPermissionEntities($this->getConfigurationOption($configuration, 'apply_to_entities', []))
            )
            ->setDescription($this->getConfigurationOption($configuration, 'description', ''));

        return $permission;
    }

    /**
     * @param array $configuration
     * @return ArrayCollection|PermissionEntity[]
     * @throws NotManageableEntityException
     */
    protected function buildPermissionEntities(array $configuration)
    {
        $permissionEntityRepository = $this->doctrineHelper
            ->getEntityRepositoryForClass('OroSecurityBundle:PermissionEntity');

        $entities = new ArrayCollection();
        foreach ($configuration as $entityName) {
            if (!$this->isManageableEntityClass($entityName)) {
                throw new NotManageableEntityException($entityName);
            }
            $entityNameNormalized = strtolower($entityName);
            if (!array_key_exists($entityNameNormalized, $this->processedEntities)) {
                $permissionEntity = $permissionEntityRepository->findOneBy(['name' => $entityName]);
                if (!$permissionEntity) {
                    $permissionEntity = new PermissionEntity();
                    $permissionEntity->setName($entityName);
                }
                $this->processedEntities[$entityNameNormalized] = $permissionEntity;
            }
            $entities->add($this->processedEntities[$entityNameNormalized]);
        }

        return $entities;
    }

    /**
     * @param array $configuration
     * @param array $requiredOptions
     * @throws MissedRequiredOptionException
     */
    protected function assertConfigurationOptions(array $configuration, array $requiredOptions)
    {
        foreach ($requiredOptions as $optionName) {
            if (!isset($configuration[$optionName])) {
                throw new MissedRequiredOptionException(sprintf('Configuration option "%s" is required', $optionName));
            }
        }
    }

    /**
     * @param array $options
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfigurationOption(array $options, $key, $default = null)
    {
        if (array_key_exists($key, $options)) {
            return $options[$key];
        }

        return $default;
    }

    /**
     * @param $entityClass
     * @return mixed
     */
    protected function isManageableEntityClass($entityClass)
    {
        try {
            return $isManageable = $this->doctrineHelper->isManageableEntityClass($entityClass);
        } catch (\Exception $e) {
            return false;
        }
    }
}
