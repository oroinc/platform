<?php

namespace Oro\Bundle\CronBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use JMS\JobQueueBundle\Entity\Job;

class JmsJobCountCommand extends ContainerAwareCommand
{
    /**
     * Jms job states
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
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:jms-job-queue:count')
            ->setDescription(sprintf(
                'Shows a number of jobs in one of the defined states (%s). By default: %s.',
                implode(', ', $this->states),
                Job::STATE_PENDING
            ))
            ->addOption(
                'state',
                null,
                InputOption::VALUE_REQUIRED,
                'Identifiers of the process jobs',
                Job::STATE_PENDING
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $state = $input->getOption('state');
        if (!in_array($state, $this->states, true)) {
            $output->writeln(sprintf(
                '<error>Invalid state. Use one of this: %s.</error>',
                implode(', ', $this->states)
            ));

            return;
        }

        $output->writeln($this->getJobsCount($state));
    }

    /**
     * Get Jms jobs count by state.
     *
     * @param $state
     *
     * @return int
     */
    protected function getJobsCount($state)
    {
        return
            $this->getContainer()
                ->get('doctrine')
                ->getRepository('JMSJobQueueBundle:Job')
                ->createQueryBuilder('job')
                ->select('COUNT(job.id)')
                ->where('job.state = :state')
                ->setParameter('state', $state)
                ->getQuery()
                ->getSingleScalarResult();
    }
}
