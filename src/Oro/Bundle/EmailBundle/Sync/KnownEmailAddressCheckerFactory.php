<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

/**
 * The factory to create a service that is responsible for checking known email addresses.
 */
class KnownEmailAddressCheckerFactory
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private EmailAddressManager $emailAddressManager,
        private EmailAddressHelper $emailAddressHelper,
        private EmailOwnerProviderStorage $emailOwnerProviderStorage,
        private array $exclusions = []
    ) {
    }

    /**
     * Creates new instance of a class responsible for checking known email addresses.
     */
    public function create(): KnownEmailAddressCheckerInterface
    {
        return new KnownEmailAddressChecker(
            $this->getEntityManager(),
            $this->emailAddressManager,
            $this->emailAddressHelper,
            $this->emailOwnerProviderStorage,
            $this->exclusions
        );
    }

    private function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager();
        if (!$em->isOpen()) {
            $this->doctrine->resetManager();
            $em = $this->doctrine->getManager();
        }

        return $em;
    }
}
