<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var array
     */
    private $processedEntities = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ValidatorInterface $validator
     */
    public function __construct(DoctrineHelper $doctrineHelper, ValidatorInterface $validator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->validator = $validator;
    }

    /**
     * @param array $configuration
     * @return Permission[]|Collection
     */
    public function buildPermissions(array $configuration)
    {
        $permissions = new ArrayCollection();
        foreach ($configuration as $name => $permissionConfiguration) {
            $permission = $this->buildPermission($name, $permissionConfiguration);

            $violations = $this->validator->validate($permission);
            if ($violations->count() > 0) {
                throw $this->createValidationException($name, $violations);
            }

            $permissions->add($permission);
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

        $excludeEntities = $this->getConfigurationOption($configuration, 'exclude_entities', []);
        $applyToEntities = $this->getConfigurationOption($configuration, 'apply_to_entities', []);

        $permission = new Permission();
        $permission
            ->setName($name)
            ->setLabel($configuration['label'])
            ->setApplyToAll($this->getConfigurationOption($configuration, 'apply_to_all', true))
            ->setGroupNames($this->getConfigurationOption($configuration, 'group_names', []))
            ->setExcludeEntities($this->buildPermissionEntities($excludeEntities))
            ->setApplyToEntities($this->buildPermissionEntities($applyToEntities))
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
        $repository = $this->doctrineHelper->getEntityRepositoryForClass('OroSecurityBundle:PermissionEntity');

        $entities = new ArrayCollection();
        $configuration = array_unique($configuration);
        foreach ($configuration as $entityName) {
            $entityNameNormalized = strtolower($entityName);

            if (!array_key_exists($entityNameNormalized, $this->processedEntities)) {
                $permissionEntity = $repository->findOneBy(['name' => $entityName]);

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
     * @param string $name
     * @param ConstraintViolationListInterface $violations
     * @return ValidatorException
     */
    protected function createValidationException($name, ConstraintViolationListInterface $violations)
    {
        $errors = '';

        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $errors .= sprintf('    %s%s', $violation->getMessage(), PHP_EOL);
        }

        return new ValidatorException(
            sprintf('Configuration of permission %s is invalid:%s%s', $name, PHP_EOL, $errors)
        );
    }
}
