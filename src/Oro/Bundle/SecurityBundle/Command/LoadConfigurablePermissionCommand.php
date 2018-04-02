<?php

namespace Oro\Bundle\SecurityBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadConfigurablePermissionCommand extends ContainerAwareCommand
{
    const NAME = 'security:configurable-permission:load';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Load configurable permissions into cache')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command load Configurable Permission into cache:

  <info>php %command.full_name%</info>

Usually you need to run this command when you update the configuration file 
<comment>Resources/config/oro/configurable_permissions.yml</comment>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $provider = $this->getContainer()->get('oro_security.acl.configurable_permission_provider');
        $provider->buildCache();

        $output->writeln(sprintf('<info>All configurable permissions successfully loaded into cache.</info>'));
    }
}
