<?php

namespace Oro\Bundle\EmailBundle\Command;

use Doctrine\ORM\EntityManager;
use JMS\JobQueueBundle\Entity\Job;
use Oro\Bundle\EmailBundle\Command\Manager\AssociationManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAssociationsCommand extends ContainerAwareCommand
{
    const NAME = 'oro:email:update-associations';
    const OPTION_FOREGROUND = 'foreground';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:email:update-associations')
            ->setDescription('Update associations to emails')
            ->addOption(
                static::OPTION_FOREGROUND,
                'f',
                InputOption::VALUE_NONE,
                'Schedule jobs in backround and exits immediately'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption(static::OPTION_FOREGROUND)) {
            $this
                ->getCommandAssociationManager()
                ->processUpdateAllEmailOwners();
        } else {
            $job = new Job(static::NAME, ['--foreground']);

            $em = $this->getJobManager();
            $em->persist($job);
            $em->flush();
        }

        $output->writeln('<info>Update of associations has been scheduled.</info>');
    }

    /**
     * @return EntityManager
     */
    public function getJobManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(Job::class);
    }

    /**
     * @return AssociationManager
     */
    protected function getCommandAssociationManager()
    {
        return $this->getContainer()->get('oro_email.command.association_manager');
    }
}
