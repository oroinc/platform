<?php

namespace Oro\Bundle\SecurityBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;

class LoadPermissionConfigurationCommand extends ContainerAwareCommand
{
    const NAME = 'security:permission:configuration:load';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Load permissions configuration from configuration files to the database')
            ->addOption(
                'permissions',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Names of the permissions that should be loaded'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $acceptedPermissions = $input->getOption('permissions') ?: null;

        $manager = $this->getContainer()->get('oro_security.acl.permission_manager');

        $permissions = $manager->getPermissionsFromConfig($acceptedPermissions);
        if ($permissions) {
            $output->writeln('Loading permissions...');

            $permissions = $manager->processPermissions($permissions);

            foreach ($permissions as $permission) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $permission->getName()));
                $this->validatePermissionEntities($permission, $output);
            }
        } else {
            $output->writeln('No permissions found.');
        }
    }

    /**
     * @param Permission $permission
     * @param OutputInterface $output
     * @return array
     */
    protected function validatePermissionEntities(Permission $permission, OutputInterface $output)
    {
        /** @var PermissionEntity[] $permissionEntities */
        $permissionEntities = array_merge(
            $permission->getApplyToEntities()->toArray(),
            $permission->getExcludeEntities()->toArray()
        );

        foreach ($permissionEntities as $permissionEntity) {
            if (!$this->isManageableEntityClass($permissionEntity->getName())) {
                $output->writeln(sprintf(
                    '    <comment>></comment> <error>%s - is not a manageable entity class</error>',
                    $permissionEntity->getName()
                ));
            }
        }
    }

    /**
     * @param string $entityClass
     * @return bool
     */
    protected function isManageableEntityClass($entityClass)
    {
        if (!$this->doctrineHelper) {
            $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        }

        try {
            return $this->doctrineHelper->isManageableEntityClass($entityClass);
        } catch (\Exception $e) {
            return false;
        }
    }
}
