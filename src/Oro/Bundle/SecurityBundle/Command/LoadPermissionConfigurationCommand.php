<?php
declare(strict_types=1);

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
 * Loads permission configuration to the database.
 */
class LoadPermissionConfigurationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'security:permission:configuration:load';

    private PermissionManager $permissionManager;
    private PermissionConfigurationProvider $permissionConfigurationProvider;
    private PermissionConfigurationBuilder $permissionConfigurationBuilder;
    private DoctrineHelper $doctrineHelper;

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

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption(
                'permissions',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Permissions to load'
            )
            ->setDescription('Loads permission configuration to the database.')
            ->setHelp(
                // @codingStandardsIgnoreStart
                <<<'HELP'
The <info>%command.name%</info> command loads permission configuration from the configuration files to the database.

  <info>php %command.full_name%</info>

The <info>--permissions</info> option can be used to load only the specified permission configurations:

  <info>php %command.full_name% --permissions=<permission1> --permissions=<permission2> --permissions=<permissionN></info>

HELP
                // @codingStandardsIgnoreEnd
            )
            ->addUsage('--permissions=<permission1> --permissions=<permission2> --permissions=<permissionN>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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

            return 1;
        }

        return 0;
    }

    protected function validatePermissionEntities(Permission $permission, OutputInterface $output): void
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

    protected function isManageableEntityClass(string $entityClass): bool
    {
        try {
            return $this->doctrineHelper->isManageableEntityClass($entityClass);
        } catch (\Exception $e) {
            return false;
        }
    }
}
