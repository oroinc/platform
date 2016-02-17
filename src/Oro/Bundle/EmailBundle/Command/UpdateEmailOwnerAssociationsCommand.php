<?php

namespace Oro\Bundle\EmailBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->addArgument(static::OWNER_CLASS_ARGUMENT, InputArgument::REQUIRED)
            ->addArgument(static::OWNER_ID_ARGUMENT, InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $owner = $this->findOwner(
            $input->getArgument(static::OWNER_CLASS_ARGUMENT),
            $input->getArgument(static::OWNER_ID_ARGUMENT)
        );

        if (!$owner) {
            $output->writeln('<info>Object not found for given input.</info>');

            return;
        }

        $emails = $this->getEmailOwnersProvider()->getEmailsByOwnerEntity($owner);
        foreach ($emails as $email) {
            $this->getEmailActivityManager()->addAssociation($email, $owner);
        }

        if ($emails) {
            $this->getEmailEntityManager()->flush();
        }

        $output->writeln(sprintf('<info>Associated %d emails with given object.</info>', count($emails)));
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
        return $this->getRegistry()->getManagerForClass('OroEmailBundle:Email');
    }

    /**
     * @param string $class
     * @param mixed $id
     *
     * @return object|null
     */
    protected function findOwner($class, $id)
    {
        return $this->getRegistry()->getRepository($class)->find($id);
    }

    /**
     * @return Registry
     */
    protected function getRegistry()
    {
        return $this->getContainer()->get('doctrine');
    }
}
