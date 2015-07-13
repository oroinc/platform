<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

class KnownEmailAddressCheckerFactory
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var EmailAddressManager */
    protected $emailAddressManager;

    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    /** @var EmailOwnerProviderStorage */
    protected $emailOwnerProviderStorage;

    /** @var string[] */
    protected $exclusions;

    /**
     * @param ManagerRegistry           $doctrine
     * @param EmailAddressManager       $emailAddressManager
     * @param EmailAddressHelper        $emailAddressHelper
     * @param EmailOwnerProviderStorage $emailOwnerProviderStorage
     * @param string[]                  $exclusions Class names of email address owners which should be excluded
     */
    public function __construct(
        ManagerRegistry $doctrine,
        EmailAddressManager $emailAddressManager,
        EmailAddressHelper $emailAddressHelper,
        EmailOwnerProviderStorage $emailOwnerProviderStorage,
        $exclusions = []
    ) {
        $this->doctrine                  = $doctrine;
        $this->emailAddressManager       = $emailAddressManager;
        $this->emailAddressHelper        = $emailAddressHelper;
        $this->emailOwnerProviderStorage = $emailOwnerProviderStorage;
        $this->exclusions                = $exclusions;
    }

    /**
     * Creates new instance of a class responsible for checking known email addresses
     *
     * @return KnownEmailAddressCheckerInterface
     */
    public function create()
    {
        return new KnownEmailAddressChecker(
            $this->getEntityManager(),
            $this->emailAddressManager,
            $this->emailAddressHelper,
            $this->emailOwnerProviderStorage,
            $this->exclusions
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
