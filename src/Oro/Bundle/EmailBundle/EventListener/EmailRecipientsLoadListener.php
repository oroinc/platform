<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository;
use Oro\Bundle\EmailBundle\Event\EmailRecipientsLoadEvent;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

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

    /**
     * @param SecurityFacade $securityFacade
     * @param AclHelper $aclHelper
     * @param RelatedEmailsProvider $relatedEmailsProvider
     * @param Registry $registry
     * @param TranslatorInterface $translator
     */
    public function __construct(
        SecurityFacade $securityFacade,
        AclHelper $aclHelper,
        RelatedEmailsProvider $relatedEmailsProvider,
        Registry $registry,
        TranslatorInterface $translator
    ) {
        $this->securityFacade = $securityFacade;
        $this->aclHelper = $aclHelper;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->registry = $registry;
        $this->translator = $translator;
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
                    'children' => $this->createResultFromEmails($recentlyUsedEmails),
                ],
            ]
        ));
    }

    /**
     * @param EmailRecipientsLoadEvent $event
     */
    public function loadContextEmails(EmailRecipientsLoadEvent $event)
    {
        $query = $event->getQuery();
        $limit = $event->getRemainingLimit();

        if (!$limit || !$event->getRelatedEntity()) {
            return;
        }

        $emails = $this->relatedEmailsProvider->getEmails($event->getRelatedEntity(), 2);

        $excludedEmails = $event->getEmails();
        $filteredEmails = array_filter($emails, function ($email) use ($query, $excludedEmails) {
            return !in_array($email, $excludedEmails) && stripos($email, $query) !== false;
        });
        if (!$filteredEmails) {
            return;
        }

        $id = $this->translator->trans('oro.email.autocomplete.contexts');
        $resultsId = null;
        $results = $event->getResults();
        foreach ($results as $recordId => $record) {
            if ($record['text'] === $id) {
                $resultsId = $recordId;

                break;
            }
        }

        $children = $this->createResultFromEmails(array_splice($filteredEmails, 0, $limit));
        if ($resultsId !== null) {
            $results[$resultsId]['children'] = array_merge($results[$resultsId]['children'], $children);
        } else {
            $results = array_merge(
                $results,
                [
                    [
                        'text'     => $id,
                        'children' => $children,
                    ],
                ]
            );
        }

        $event->setResults($results);
    }

    /**
     * @param array $emails
     *
     * @return array
     */
    protected function createResultFromEmails(array $emails)
    {
        $result = [];
        foreach ($emails as $email => $name) {
            $result[] = [
                'id'   => $email,
                'text' => $name,
            ];
        }

        return $result;
    }

    /**
     * @return EmailRecipientRepository
     */
    protected function getEmailRecipientRepository()
    {
        return $this->registry->getRepository('OroEmailBundle:EmailRecipient');
    }
}
