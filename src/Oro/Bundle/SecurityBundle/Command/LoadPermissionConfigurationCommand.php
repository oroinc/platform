<?php

namespace Oro\Bundle\SecurityBundle\Command;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\SecurityBundle\Entity\Permission;

class LoadPermissionConfigurationCommand extends ContainerAwareCommand
{
    const NAME = 'oro:permission:configuration:load';

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

            /** @var EntityManager $entityManager */
            $entityManager = $this->getContainer()->get('doctrine')
                ->getManagerForClass('OroSecurityBundle:Permission');

            foreach ($permissions as $permission) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $permission->getName()));
                $entityManager->persist($manager->preparePermissionForDb($permission));

                foreach ($manager->getNotManageableEntities($permission) as $entityClass) {
                    $output->writeln(sprintf(
                        '    <comment>></comment> <error>%s - is not a manageable entity class</error>',
                        $entityClass
                    ));
                }
            }

            $entityManager->flush();
        } else {
            $output->writeln('No permissions found.');
        }
    }
}
