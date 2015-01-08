<?php

namespace Oro\Bundle\ImapBundle\Sync;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerInterface;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;

class ImapEmailSynchronizationProcessorFactory
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var EmailEntityBuilder */
    protected $emailEntityBuilder;

    /**
     * @param ManagerRegistry    $doctrine
     * @param EmailEntityBuilder $emailEntityBuilder
     */
    public function __construct(
        ManagerRegistry $doctrine,
        EmailEntityBuilder $emailEntityBuilder
    ) {
        $this->doctrine           = $doctrine;
        $this->emailEntityBuilder = $emailEntityBuilder;
    }

    /**
     * Creates new instance of IMAP email synchronization processor
     *
     * @param ImapEmailManager                  $emailManager
     * @param KnownEmailAddressCheckerInterface $knownEmailAddressChecker
     *
     * @return ImapEmailSynchronizationProcessor
     */
    public function create(
        ImapEmailManager $emailManager,
        KnownEmailAddressCheckerInterface $knownEmailAddressChecker
    ) {
        return new ImapEmailSynchronizationProcessor(
            $this->getEntityManager(),
            $this->emailEntityBuilder,
            $knownEmailAddressChecker,
            $emailManager
        );
    }

    /**
     * Returns default entity manager
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        if (!$em->isOpen()) {
            $this->doctrine->resetManager();
            $em = $this->doctrine->getManager();
        }

        return $em;
    }
}
