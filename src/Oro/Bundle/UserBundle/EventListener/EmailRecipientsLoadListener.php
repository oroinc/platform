<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Event\EmailRecipientsLoadEvent;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;

class EmailRecipientsLoadListener
{
    /** @var Registry */
    protected $registry;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var EmailRecipientsHelper */
    protected $emailRecipientsHelper;

    /** @var DQLNameFormatter */
    protected $nameFormatter;

    /**
     * @param Registry $registry
     * @param AclHelper $aclHelper
     * @param TranslatorInterface $translator
     * @param EmailRecipientsHelper $emailRecipientsHelper
     * @param DQLNameFormatter $nameFormatter
     */
    public function __construct(
        Registry $registry,
        AclHelper $aclHelper,
        TranslatorInterface $translator,
        EmailRecipientsHelper $emailRecipientsHelper,
        DQLNameFormatter $nameFormatter
    ) {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
        $this->translator = $translator;
        $this->emailRecipientsHelper = $emailRecipientsHelper;
        $this->nameFormatter = $nameFormatter;
    }

    /**
     * @param EmailRecipientsLoadEvent $event
     */
    public function onLoad(EmailRecipientsLoadEvent $event)
    {
        $query = $event->getQuery();
        $limit = $event->getRemainingLimit();

        if (!$limit) {
            return;
        }

        $fullNameQueryPart = $this->nameFormatter->getFormattedNameDQL(
            'u',
            'Oro\Bundle\UserBundle\Entity\User'
        );

        $userEmails = $this->getUserRepository()
            ->getEmails($this->aclHelper, $fullNameQueryPart, $event->getEmails(), $query, $limit);
        if (!$userEmails) {
            return;
        }

        $event->setResults(array_merge(
            $event->getResults(),
            [
                [
                    'text'     => $this->translator->trans('oro.user.entity_plural_label'),
                    'children' => $this->emailRecipientsHelper->createResultFromEmails($userEmails),
                ],
            ]
        ));
    }

    /**
     * @return UserRepository
     */
    protected function getUserRepository()
    {
        return $this->registry->getRepository('OroUserBundle:User');
    }
}
