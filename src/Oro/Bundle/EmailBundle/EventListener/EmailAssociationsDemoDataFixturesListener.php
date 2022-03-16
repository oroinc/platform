<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PlatformBundle\EventListener\AbstractDemoDataFixturesListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

/**
 * Disables updating email associations during loading of demo data
 * and triggers it after demo data are loaded.
 */
class EmailAssociationsDemoDataFixturesListener extends AbstractDemoDataFixturesListener
{
    private AssociationManager $associationManager;
    private ManagerRegistry $doctrine;
    private EmailAddressVisibilityManager $emailAddressVisibilityManager;

    public function __construct(
        OptionalListenerManager $listenerManager,
        AssociationManager $associationManager,
        ManagerRegistry $doctrine,
        EmailAddressVisibilityManager $emailAddressVisibilityManager
    ) {
        parent::__construct($listenerManager);
        $this->associationManager = $associationManager;
        $this->doctrine = $doctrine;
        $this->emailAddressVisibilityManager = $emailAddressVisibilityManager;
    }

    /**
     * {@inheritDoc}
     */
    protected function afterEnableListeners(MigrationDataFixturesEvent $event)
    {
        $event->log('updating email owners');
        $this->updateEmailOwners();

        $event->log('updating email address visibilities');
        $this->updateEmailAddressVisibilities();

        $event->log('updating email visibilities');
        $this->updateEmailVisibilities();
    }

    private function updateEmailOwners(): void
    {
        $this->associationManager->setQueued(false);
        $this->associationManager->processUpdateAllEmailOwners();
        $this->associationManager->setQueued(true);
    }

    private function updateEmailAddressVisibilities(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Organization::class);
        $organizations = $em->createQueryBuilder()
            ->from(Organization::class, 'o')
            ->select('o.id')
            ->getQuery()
            ->getArrayResult();
        foreach ($organizations as $organization) {
            $this->emailAddressVisibilityManager->updateEmailAddressVisibilities($organization['id']);
        }
    }

    private function updateEmailVisibilities(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Email::class);
        $emailUsers = $em->createQueryBuilder()
            ->from(EmailUser::class, 'eu')
            ->select('eu, euo, e, efa, er, era')
            ->join('eu.organization', 'euo')
            ->join('eu.email', 'e')
            ->leftJoin('e.fromEmailAddress', 'efa')
            ->leftJoin('e.recipients', 'er')
            ->leftJoin('er.emailAddress', 'era')
            ->orderBy('e.id')
            ->getQuery()
            ->getResult();
        foreach ($emailUsers as $emailUser) {
            $this->emailAddressVisibilityManager->processEmailUserVisibility($emailUser);
        }
        $em->flush();
    }
}
