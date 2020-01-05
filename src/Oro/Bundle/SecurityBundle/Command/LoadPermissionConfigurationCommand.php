<?php

namespace Oro\Bundle\SecurityBundle\Command;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Load permissions configuration from configuration files to the database.
 */
class LoadPermissionConfigurationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'security:permission:configuration:load';

    /** @var PermissionManager */
    private $permissionManager;

    /** @var PermissionConfigurationProvider */
    private $permissionConfigurationProvider;

    /** @var PermissionConfigurationBuilder */
    private $permissionConfigurationBuilder;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param PermissionManager $permissionManager
     * @param PermissionConfigurationProvider $permissionConfigurationProvider
     * @param PermissionConfigurationBuilder $permissionConfigurationBuilder
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        PermissionManager $permissionManager,
        PermissionConfigurationProvider $permissionConfigurationProvider,
        PermissionConfigurationBuilder $permissionConfigurationBuilder,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct();

        $this->permissionManager = $permissionManager;
        $this->permissionConfigurationProvider = $permissionConfigurationProvider;
        $this->permissionConfigurationBuilder = $permissionConfigurationBuilder;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription('Load permissions configuration from configuration files to the database')
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

        $permissions = $this->permissionConfigurationBuilder->buildPermissions(
            $this->permissionConfigurationProvider->getPermissionConfiguration($acceptedPermissions)
        );
        if ($permissions) {
            $output->writeln('Loading permissions...');

            $permissions = $this->permissionManager->processPermissions($permissions);

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
        try {
            return $this->doctrineHelper->isManageableEntityClass($entityClass);
        } catch (\Exception $e) {
            return false;
        }
    }
}
