<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository;
use Oro\Bundle\EmailBundle\Event\EmailRecipientsLoadEvent;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EmailRecipientsLoadListener
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /** @var Registry */
    protected $registry;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param SecurityFacade $securityFacade
     * @param RelatedEmailsProvider $relatedEmailsProvider
     * @param Registry $registry
     * @param TranslatorInterface $translator
     */
    public function __construct(
        SecurityFacade $securityFacade,
        RelatedEmailsProvider $relatedEmailsProvider,
        Registry $registry,
        TranslatorInterface $translator
    ) {
        $this->securityFacade = $securityFacade;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->registry = $registry;
        $this->translator = $translator;
    }

    /**
     * @param EmailRecipientsLoadEvent $event
     */
    public function onLoad(EmailRecipientsLoadEvent $event)
    {
        $query = $event->getQuery();
        $limit = $event->getLimit() - count($event->getResults());
        
        if (!$limit || null === $user = $this->securityFacade->getLoggedUser()) {
            return;
        }

        $userEmailAddresses = $this->relatedEmailsProvider->getEmails($user);
        $recentlyUsedEmails = $this->getEmailRecipientRepository()
            ->getEmailsUsedInLast30Days($userEmailAddresses, $query, $limit);

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
