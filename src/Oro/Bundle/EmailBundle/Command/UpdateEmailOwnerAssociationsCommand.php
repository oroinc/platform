<?php

namespace Oro\Bundle\EmailBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;

class UpdateEmailOwnerAssociationsCommand extends ContainerAwareCommand
{
    const OWNER_CLASS_ARGUMENT = 'class';
    const OWNER_ID_ARGUMENT = 'id';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:email:update-email-owner-associations')
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

        foreach ($owners as $owner) {
            $emails = $this->getEmailOwnersProvider()->getEmailsByOwnerEntity($owner);
            foreach ($emails as $email) {
                $this->getEmailActivityManager()->addAssociation($email, $owner);
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
