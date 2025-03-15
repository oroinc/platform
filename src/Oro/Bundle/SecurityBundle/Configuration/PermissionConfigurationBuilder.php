<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;
use Oro\Bundle\SecurityBundle\Exception\MissedRequiredOptionException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Builds Permissions by configuration array
 */
class PermissionConfigurationBuilder
{
    private array $processedEntities = [];

    public function __construct(
        private ValidatorInterface $validator,
        private ManagerRegistry $doctrine
    ) {
    }

    /**
     * @return Collection<int, Permission>
     */
    public function buildPermissions(array $configuration): Collection
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Permission::class);
        $classNames = $em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
        $permissions = new ArrayCollection();
        foreach ($configuration as $name => $permissionConfiguration) {
            $permission = $this->buildPermission($name, $permissionConfiguration, $classNames);

            $violations = $this->validator->validate($permission);
            if ($violations->count() > 0) {
                throw $this->createValidationException($name, $violations);
            }

            $permissions->add($permission);
        }

        $this->processedEntities = [];

        return $permissions;
    }

    private function buildPermission(string $name, array $configuration, array $classNames): Permission
    {
        $this->assertConfigurationOptions($configuration, ['label']);

        $excludeEntities = $this->getConfigurationOption($configuration, 'exclude_entities', []);
        $applyToEntities = $this->getConfigurationOption($configuration, 'apply_to_entities', []);
        $applyToInterfaces = $this->getConfigurationOption($configuration, 'apply_to_interfaces', []);
        if (!empty($applyToInterfaces)) {
            $applyToEntitiesByInterfaces = $this->getClassesByInterfaces($classNames, $applyToInterfaces);
            $applyToEntities = array_merge($applyToEntities, $applyToEntitiesByInterfaces);
        }

        $permission = new Permission();
        $permission->setName($name);
        $permission->setLabel($configuration['label']);
        $permission->setApplyToAll($this->getConfigurationOption($configuration, 'apply_to_all', true));
        $permission->setGroupNames($this->getConfigurationOption($configuration, 'group_names', []));
        $permission->setExcludeEntities($this->buildPermissionEntities($excludeEntities));
        $permission->setApplyToEntities($this->buildPermissionEntities($applyToEntities));
        $permission->setDescription($this->getConfigurationOption($configuration, 'description', ''));

        return $permission;
    }

    /**
     * @return Collection<int, PermissionEntity>
     */
    private function buildPermissionEntities(array $configuration): Collection
    {
        $repository = $this->doctrine->getRepository(PermissionEntity::class);

        $entities = new ArrayCollection();
        $configuration = array_unique($configuration);
        foreach ($configuration as $entityName) {
            $entityNameNormalized = strtolower($entityName);

            if (!\array_key_exists($entityNameNormalized, $this->processedEntities)) {
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

    private function assertConfigurationOptions(array $configuration, array $requiredOptions): void
    {
        foreach ($requiredOptions as $optionName) {
            if (!isset($configuration[$optionName])) {
                throw new MissedRequiredOptionException(
                    \sprintf('Configuration option "%s" is required', $optionName)
                );
            }
        }
    }

    private function getConfigurationOption(array $options, string $key, mixed $default = null): mixed
    {
        if (\array_key_exists($key, $options)) {
            return $options[$key];
        }

        return $default;
    }

    private function createValidationException(
        string $name,
        ConstraintViolationListInterface $violations
    ): ValidatorException {
        $errors = '';

        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $errors .= \sprintf('    %s%s', $violation->getMessage(), PHP_EOL);
        }

        return new ValidatorException(
            \sprintf('Configuration of permission %s is invalid:%s%s', $name, PHP_EOL, $errors)
        );
    }

    private function getClassesByInterfaces(array $classNames, array $configuration): array
    {
        return array_filter(
            $classNames,
            function ($class) use ($configuration) {
                foreach ($configuration as $interfaceName) {
                    $isSubClass = is_subclass_of($class, $interfaceName);
                    if ($isSubClass) {
                        return true;
                    }
                }

                return false;
            }
        );
    }
}
