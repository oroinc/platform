<?php

namespace Oro\Bundle\EmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EmailBundle\Command\Manager\AssociationManager;

class UpdateEmailOwnerAssociationsCommand extends ContainerAwareCommand
{
    const OWNER_CLASS_ARGUMENT = 'class';
    const OWNER_ID_ARGUMENT = 'id';
    const COMMAND_NAME = 'oro:email:update-email-owner-associations';
    const EMAIL_BUFFER_SIZE = 100;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Updates emails for email owner')
            ->addArgument(static::OWNER_CLASS_ARGUMENT, InputArgument::REQUIRED, 'Email owner class')
            ->addArgument(
                static::OWNER_ID_ARGUMENT,
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Email owner id[s]'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ownerClassName = $input->getArgument(static::OWNER_CLASS_ARGUMENT);
        $ownerId = $input->getArgument(static::OWNER_ID_ARGUMENT);

        $countNewJob = $this->getCommandAssociationManager()->processUpdateEmailOwner($ownerClassName, $ownerId);

        $output->writeln(sprintf('<info>Added %d new job.</info>', $countNewJob));
    }

    /**
     * @return AssociationManager
     */
    protected function getCommandAssociationManager()
    {
        return $this->getContainer()->get('oro_email.command.association_manager');
    }
}
