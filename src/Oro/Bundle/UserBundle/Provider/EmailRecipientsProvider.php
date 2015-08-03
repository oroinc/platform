<?php

namespace Oro\Bundle\UserBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;

class EmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    /** @var Registry */
    protected $registry;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var EmailRecipientsHelper */
    protected $emailRecipientsHelper;

    /** @var DQLNameFormatter */
    protected $nameFormatter;

    /**
     * @param Registry $registry
     * @param AclHelper $aclHelper
     * @param EmailRecipientsHelper $emailRecipientsHelper
     * @param DQLNameFormatter $nameFormatter
     */
    public function __construct(
        Registry $registry,
        AclHelper $aclHelper,
        EmailRecipientsHelper $emailRecipientsHelper,
        DQLNameFormatter $nameFormatter
    ) {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
        $this->emailRecipientsHelper = $emailRecipientsHelper;
        $this->nameFormatter = $nameFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(EmailRecipientsProviderArgs $args)
    {
        $fullNameQueryPart = $this->nameFormatter->getFormattedNameDQL(
            'u',
            'Oro\Bundle\UserBundle\Entity\User'
        );

        $userEmails = $this->getUserRepository()->getEmails(
            $this->aclHelper,
            $fullNameQueryPart,
            $args->getExcludedEmails(),
            $args->getQuery(),
            $args->getLimit()
        );

        return $userEmails;
    }

    /**
     * {@inheritdoc}
     */
    public function getSection()
    {
        return 'oro.user.entity_plural_label';
    }

    /**
     * @return UserRepository
     */
    protected function getUserRepository()
    {
        return $this->registry->getRepository('OroUserBundle:User');
    }
}
