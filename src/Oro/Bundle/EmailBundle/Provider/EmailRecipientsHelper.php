<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\Repository\EmailAwareRepository;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class EmailRecipientsHelper
{
     /** @var AclHelper */
    protected $aclHelper;

    /** @var DQLNameFormatter */
    protected $nameFormatter;

    /**
     * @param AclHelper $aclHelper
     * @param DQLNameFormatter $nameFormatter
     */
    public function __construct(AclHelper $aclHelper, DQLNameFormatter $nameFormatter)
    {
        $this->aclHelper = $aclHelper;
        $this->nameFormatter = $nameFormatter;
    }

    /**
     * @param EmailRecipientsProviderArgs $args
     * @param EmailAwareRepository $repository
     * @param string $alias
     * @param string $entityClass
     *
     * @return array
     */
    public function getRecipients(
        EmailRecipientsProviderArgs $args,
        EmailAwareRepository $repository,
        $alias,
        $entityClass
    ) {
        $fullNameQueryPart = $this->nameFormatter->getFormattedNameDQL($alias, $entityClass);

        $primaryEmailsQb = $repository
            ->getPrimaryEmailsQb($fullNameQueryPart, $args->getExcludedEmails(), $args->getQuery())
            ->setMaxResults($args->getLimit());

        $primaryEmailsResult = $this->aclHelper->apply($primaryEmailsQb)->getResult();
        $emails = $this->emailsFromResult($primaryEmailsResult);

        $limit = $args->getLimit() - count($emails);

        if ($limit > 0) {
            $excludedEmails = array_merge($args->getExcludedEmails(), array_keys($emails));
            $secondaryEmailsQb = $repository
                ->getSecondaryEmailsQb($fullNameQueryPart, $excludedEmails, $args->getQuery())
                ->setMaxResults($limit);

            $secondaryEmailsResult = $this->aclHelper->apply($secondaryEmailsQb)->getResult();
            $emails = array_merge($emails, $this->emailsFromResult($secondaryEmailsResult));
        }

        return $emails;
    }

    /**
     * @param EmailRecipientsProviderArgs $args
     * @param array $recipients
     *
     * @return array
     */
    public static function filterRecipients(EmailRecipientsProviderArgs $args, array $recipients)
    {
        $unExcludedEmails = array_filter(array_keys($recipients), function ($email) use ($args) {
            return !in_array($email, $args->getExcludedEmails());
        });

        $unExcludedRecipients = array_intersect_key($recipients, array_flip($unExcludedEmails));

        return array_filter($unExcludedRecipients, function ($email) use ($args) {
            return stripos($email, $args->getQuery()) !== false;
        });
    }

    /**
     * @param array $result
     *
     * @return array
     */
    protected function emailsFromResult(array $result)
    {
        $emails = [];
        foreach ($result as $row) {
            $emails[$row['email']] = sprintf('%s <%s>', $row['name'], $row['email']);
        }

        return $emails;
    }
}
