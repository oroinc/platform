<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;
use Oro\Bundle\SecurityBundle\Exception\MissedRequiredOptionException;

class PermissionConfigurationBuilder
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var EntityRepository
     */
    private $permissionEntityRepository;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param array $configuration
     * @return Permission[]
     */
    public function buildPermissions(array $configuration)
    {
        $permissions = [];
        foreach ($configuration as $name => $permissionConfiguration) {
            $permissions[] = $this->buildPermission($name, $permissionConfiguration);
        }

        return $permissions;
    }

    /**
     * @param $name
     * @param array $configuration
     * @return Permission
     */
    public function buildPermission($name, array $configuration)
    {
        $this->assertConfigurationOptions($configuration, ['label']);

        $permission = new Permission();
        $permission
            ->setName($name)
            ->setLabel($configuration['label'])
            ->setApplyToAll(array_key_exists('apply_to_all', $configuration) ? $configuration['apply_to_all'] : true)
            ->setGroupNames(array_key_exists('group_names', $configuration) ? $configuration['group_names'] : [])
            ->setExcludeEntities(
                $this->buildPermissionEntities(
                    array_key_exists('exclude_entities', $configuration) ? $configuration['exclude_entities'] : []
                )
            )
            ->setApplyToEntities(
                $this->buildPermissionEntities(
                    array_key_exists('apply_to_entities', $configuration) ? $configuration['apply_to_entities'] : []
                )
            )
            ->setDescription(array_key_exists('description', $configuration) ? $configuration['description'] : '');

        return $permission;
    }

    /**
     * @param array $configuration
     * @return ArrayCollection|PermissionEntity[]
     */
    public function buildPermissionEntities(array $configuration)
    {
        $entities = new ArrayCollection();
        foreach ($configuration as $entityName) {
            $permissionEntity = $this->getPermissionRepository()->findOneBy(['name' => $entityName]);
            if (!$permissionEntity) {
                $permissionEntity = new PermissionEntity();
                $permissionEntity->setName($entityName);
            }
            $entities->add($permissionEntity);
        }

        return $entities;
    }

    /**
     * @return EntityRepository
     */
    protected function getPermissionRepository()
    {
        if (!$this->permissionEntityRepository) {
            $this->permissionEntityRepository = $this->registry
                ->getManagerForClass('OroSecurityBundle:PermissionEntity')
                ->getRepository('OroSecurityBundle:PermissionEntity');
        }

        return $this->permissionEntityRepository;
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
}
