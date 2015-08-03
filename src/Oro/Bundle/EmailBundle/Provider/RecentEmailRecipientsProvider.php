<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class RecentEmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var Registry */
    protected $registry;

    /**
     * @param SecurityFacade $securityFacade
     * @param RelatedEmailsProvider $relatedEmailsProvider
     * @param AclHelper $aclHelper
     * @param Registry $registry
     */
    public function __construct(
        SecurityFacade $securityFacade,
        RelatedEmailsProvider $relatedEmailsProvider,
        AclHelper $aclHelper,
        Registry $registry
    ) {
        $this->securityFacade = $securityFacade;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->aclHelper = $aclHelper;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(EmailRecipientsProviderArgs $args)
    {
        if (null === $user = $this->securityFacade->getLoggedUser()) {
            return [];
        }

        $userEmailAddresses = array_keys($this->relatedEmailsProvider->getEmails($user));

        return $this->getEmailRecipientRepository()
            ->getEmailsUsedInLast30Days(
                $this->aclHelper,
                $userEmailAddresses,
                $args->getExcludedEmails(),
                $args->getQuery(),
                $args->getLimit()
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getSection()
    {
        return 'oro.email.autocomplete.recently_used';
    }

    /**
     * @return EmailRecipientRepository
     */
    protected function getEmailRecipientRepository()
    {
        return $this->registry->getRepository('OroEmailBundle:EmailRecipient');
    }
}
