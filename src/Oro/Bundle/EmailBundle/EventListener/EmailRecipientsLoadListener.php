<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository;
use Oro\Bundle\EmailBundle\Event\EmailRecipientsLoadEvent;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EmailRecipientsLoadListener
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /** @var Registry */
    protected $registry;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var EmailRecipientsHelper */
    protected $emailRecipientsHelper;

    /**
     * @param SecurityFacade $securityFacade
     * @param AclHelper $aclHelper
     * @param RelatedEmailsProvider $relatedEmailsProvider
     * @param Registry $registry
     * @param TranslatorInterface $translator
     * @param EmailRecipientsHelper $emailRecipientsHelper
     */
    public function __construct(
        SecurityFacade $securityFacade,
        AclHelper $aclHelper,
        RelatedEmailsProvider $relatedEmailsProvider,
        Registry $registry,
        TranslatorInterface $translator,
        EmailRecipientsHelper $emailRecipientsHelper
    ) {
        $this->securityFacade = $securityFacade;
        $this->aclHelper = $aclHelper;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->registry = $registry;
        $this->translator = $translator;
        $this->emailRecipientsHelper = $emailRecipientsHelper;
    }

    /**
     * @param EmailRecipientsLoadEvent $event
     */
    public function loadRecentEmails(EmailRecipientsLoadEvent $event)
    {
        $query = $event->getQuery();
        $limit = $event->getRemainingLimit();
        
        if (!$limit || null === $user = $this->securityFacade->getLoggedUser()) {
            return;
        }

        $userEmailAddresses = array_keys($this->relatedEmailsProvider->getEmails($user));
        $recentlyUsedEmails = $this->getEmailRecipientRepository()
            ->getEmailsUsedInLast30Days($this->aclHelper, $userEmailAddresses, $event->getEmails(), $query, $limit);
        if (!$recentlyUsedEmails) {
            return;
        }

        $event->setResults(array_merge(
            $event->getResults(),
            [
                [
                    'text'     => $this->translator->trans('oro.email.autocomplete.recently_used'),
                    'children' => $this->emailRecipientsHelper->createResultFromEmails($recentlyUsedEmails),
                ],
            ]
        ));
    }

    /**
     * @param EmailRecipientsLoadEvent $event
     */
    public function loadContextEmails(EmailRecipientsLoadEvent $event)
    {
        $limit = $event->getRemainingLimit();
        if (!$limit || !$event->getRelatedEntity()) {
            return;
        }

        $emails = $this->relatedEmailsProvider->getEmails($event->getRelatedEntity(), 2);
        $this->emailRecipientsHelper->addEmailsToContext($event, $emails);
    }

    /**
     * @return EmailRecipientRepository
     */
    protected function getEmailRecipientRepository()
    {
        return $this->registry->getRepository('OroEmailBundle:EmailRecipient');
    }
}
