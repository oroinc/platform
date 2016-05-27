<?php

namespace Oro\Bundle\EmailBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;
use Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager;

class UpdateEmailOwnerAssociationsCommand extends ContainerAwareCommand
{
    const OWNER_CLASS_ARGUMENT = 'class';
    const OWNER_ID_ARGUMENT = 'id';
    const COMMAND_NAME = 'oro:email:update-email-owner-associations';

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
        $qb = $this->createOwnerQb(
            $input->getArgument(static::OWNER_CLASS_ARGUMENT),
            $input->getArgument(static::OWNER_ID_ARGUMENT)
        );

        $owners = (new BufferedQueryResultIterator($qb))
            ->setBufferSize(1)
            ->setPageCallback(function () {
                $this->getEmailEntityManager()->flush();
                $this->getEmailEntityManager()->clear();
            });

        $dependenseJob = null;

        foreach ($owners as $owner) {
            $emailsQB = $this->getEmailOwnersProvider()->getQBEmailsByOwnerEntity($owner);

            foreach ($emailsQB as $emailQB)
            {
                $emailId = [];
                $emails = (new BufferedQueryResultIterator($emailQB))
                    ->setBufferSize(100)
                    ->setPageCallback(function () use($output, &$owner, $input, &$emailId, &$dependenseJob) {
                        $this->getEmailEntityManager()->clear('Oro\Bundle\EmailBundle\Entity\Email');
                        $this->getEmailEntityManager()->clear('Oro\Bundle\EmailBundle\Entity\EmailBody');
                        $this->getEmailEntityManager()->clear('OroCRM\Bundle\ContactBundle\Entity\Contact');
                        $this->getEmailEntityManager()->clear('Oro\Bundle\ActivityListBundle\Entity\ActivityList');
                        $this->getEmailEntityManager()->clear('Oro\Bundle\EmailBundle\Entity\EmailThread');
                        $this->getEmailEntityManager()->clear('OroEntityProxy\OroEmailBundle\EmailAddressProxy');
                        $this->getEmailEntityManager()->clear('OroCRM\Bundle\ContactBundle\Entity\ContactPhone');


                        foreach ($emailId as $id) {
                            $arguments[] = '--id='.$id;
                        }
                        $arguments[] = '--targetClass=' . $input->getArgument(static::OWNER_CLASS_ARGUMENT);
                        $arguments[] = '--targetId=' . $owner->getId();

                        $job = new Job(AddAssociationCommand::COMMAND_NAME, $arguments);
                        if ($dependenseJob) {
                            $job->addDependency($dependenseJob);
                        }

                        $this->getDoctrineHelper()->getEntityManager($job)->persist($job);
                        $this->getDoctrineHelper()->getEntityManager($job)->flush();
                        $emailId = [];

                        $dependenseJob = $job;

                        $mem_usage = memory_get_usage(true);
                        $output->writeln(sprintf(
                            '<info>Usage memory %s.</info>',
                            round($mem_usage/1048576,2)." megabytes"
                        ));
                    });

                $output->writeln(sprintf(
                        '<info>Email count %d.</info>',
                        count($emails)
                    )
                );
                
                foreach ($emails as $email) {
                    $emailId[] = $email->getId();
                }
            }

            $output->writeln(sprintf(
                '<info>Associated %d emails with object with id %d.</info>',
                count($emails),
                $this->getDoctrineHelper()->getSingleEntityIdentifier($owner)
            ));
        }
    }

    /**
     * @return EmailActivityManager
     */
    protected function getEmailActivityManager()
    {
        return $this->getContainer()->get('oro_email.email.activity.manager');
    }

    /**
     * @return EmailOwnersProvider
     */
    protected function getEmailOwnersProvider()
    {
        return $this->getContainer()->get('oro_email.provider.emailowners.provider');
    }

    /**
     * @return ScheduleManager
     */
    protected function getScheduleManager()
    {
        return $this->getContainer()->get('oro_cron.schedule_manager');
    }

    /**
     * @return EntityManager
     */
    protected function getEmailEntityManager()
    {
        return $this->getDoctrineHelper()->getEntityManagerForClass('OroEmailBundle:Email');
    }

    /**
     * @param string $class
     * @param array $ids
     *
     * @return QueryBuilder
     */
    protected function createOwnerQb($class, array $ids)
    {
        $qb = $this->getDoctrineHelper()->getEntityRepositoryForClass($class)
            ->createQueryBuilder('o');

        return $qb
            ->andWhere($qb->expr()->in(
                sprintf('o.%s', $this->getDoctrineHelper()->getSingleEntityIdentifierFieldName($class)),
                ':ids'
            ))
            ->setParameter('ids', $ids);
    }

    /**
     * @return DoctrineHelper
     */
    protected function getDoctrineHelper()
    {
        return $this->getContainer()->get('oro_entity.doctrine_helper');
    }
}
