<?php

namespace Oro\Bundle\EmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddAssociationCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:email:add-associations';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription('Add association to emails')
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Id of email to add association'
            )
            ->addOption(
                'targetClass',
                null,
                InputOption::VALUE_REQUIRED,
                'Class name of target for email'
            )
            ->addOption(
                'targetId',
                null,
                InputOption::VALUE_REQUIRED,
                'Id of target for email'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $countNewAssociations = $this->getCommandAssociationManager()->processAddAssociation(
            $input->getOption('id'),
            $input->getOption('targetClass'),
            $input->getOption('targetId')
        );

        $output->writeln(sprintf('<info>Added %d association.</info>', $countNewAssociations));
    }

    /**
     * @return Manager\AssociationManager
     */
    protected function getCommandAssociationManager()
    {
        return $this->getContainer()->get('oro_email.command.association_manager');
    }
}
