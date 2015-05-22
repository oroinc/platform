<?php

namespace Oro\Bundle\CronBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use JMS\JobQueueBundle\Entity\Job;

class JobsCountCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:process:count';
    const DEFAULT_STATE = Job::STATE_PENDING;

    /**
     * @var array
     */
    protected $states = [
        Job::STATE_NEW,
        Job::STATE_PENDING,
        Job::STATE_CANCELED,
        Job::STATE_RUNNING,
        Job::STATE_FINISHED,
        Job::STATE_FAILED,
        Job::STATE_TERMINATED,
        Job::STATE_INCOMPLETE
    ];

    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription(sprintf(
                'Shows count of jobs in defined state (%s). By default: %s',
                implode(', ', $this->states),
                self::DEFAULT_STATE
            ))
            ->addOption(
                'state',
                null,
                InputOption::VALUE_REQUIRED,
                'Identifiers of the process jobs'
            )
        ;
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $state = self::DEFAULT_STATE;

        if ($optionState = $input->getOption('state')) {
            if (in_array($optionState, $this->states)) {
                $state = $optionState;
            } else {
                $output->writeln(sprintf(
                    '<error>Invalid state. Use one of this: %s</error>',
                    implode(', ', $this->states)
                ));
                return;
            }
        }

        $output->writeln($this->getContainer()->get('oro_cron.jms_job_helper')->getPendingJobsCount($state));
    }
}
