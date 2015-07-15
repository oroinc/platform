<?php

namespace Oro\Bundle\ImapBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ClearInactiveMailboxes extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var bool
     */
    protected $singleMailboxMode;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->singleMailboxMode) {
            return;
        }

        $jmsJob = new Job('oro:imap:clear-mailbox');
        $manager->persist($jmsJob);
        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        if ($container !== null) {
            $this->singleMailboxMode = $container->getParameter('oro_email.single_mailbox_mode');
        }
    }
}
