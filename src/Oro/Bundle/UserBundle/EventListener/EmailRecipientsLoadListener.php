<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Event\EmailRecipientsLoadEvent;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class EmailRecipientsLoadListener
{
    /** @var Registry */
    protected $registry;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param Registry $registry
     * @param AclHelper $aclHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(Registry $registry, AclHelper $aclHelper, TranslatorInterface $translator)
    {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
        $this->translator = $translator;
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

        $userEmails = $this->getUserRepository()->getEmails($this->aclHelper, $event->getEmails(), $query, $limit);
        if (!$userEmails) {
            return;
        }

        $event->setResults(array_merge(
            $event->getResults(),
            [
                [
                    'text'     => $this->translator->trans('oro.user.entity_plural_label'),
                    'children' => $this->createResultFromEmails($userEmails),
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
     * @return UserRepository
     */
    protected function getUserRepository()
    {
        return $this->registry->getRepository('OroUserBundle:User');
    }
}
