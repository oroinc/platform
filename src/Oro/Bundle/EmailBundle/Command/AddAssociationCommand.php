<?php

namespace Oro\Bundle\EmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;

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
        $target = $this->getDoctrineHelper()
            ->getEntityRepository($input->getOption('targetClass'))
            ->find($input->getOption('targetId'));

        $ids = $input->getOption('id');
        foreach ($ids as $id) {
            $email = $this->getDoctrineHelper()->getEntityRepository('Oro\Bundle\EmailBundle\Entity\Email')->find($id);
            $this->getEmailActivityManager()->addAssociation($email, $target);
        }

        $this->getDoctrineHelper()->getEntityManager('Oro\Bundle\EmailBundle\Entity\Email')->flush();
        $output->writeln(sprintf('<info>Update %d emails.</info>', count($ids)));
    }


    /**
     * @return EmailActivityManager
     */
    protected function getEmailActivityManager()
    {
        return $this->getContainer()->get('oro_email.email.activity.manager');
    }

    /**
     * @return DoctrineHelper
     */
    protected function getDoctrineHelper()
    {
        return $this->getContainer()->get('oro_entity.doctrine_helper');
    }
}
