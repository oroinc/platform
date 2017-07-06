<?php

namespace Oro\Bundle\EmailBundle\Model;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class EmailRecipientsProviderArgs
{
    /** @var object|null */
    protected $relatedEntity;

    /** @var string */
    protected $query;

    /** @var int */
    protected $limit;

    /** @var Recipient[] */
    private $excludedRecipients;

    /** @var Organization|null */
    protected $organization;

    /**
     * @param object|null $relatedEntity
     * @param string $query
     * @param int $limit
     * @param Recipient[] $excludedRecipients
     * @param Organization|null $organization
     */
    public function __construct(
        $relatedEntity,
        $query,
        $limit,
        array $excludedRecipients = [],
        Organization $organization = null
    ) {
        $this->relatedEntity = $relatedEntity;
        $this->query = $query;
        $this->limit = $limit;
        $this->excludedRecipients = $excludedRecipients;
        $this->organization = $organization;
    }

    /**
     * @return object|null
     */
    public function getRelatedEntity()
    {
        return $this->relatedEntity;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return array
     */
    public function getExcludedRecipientIdentifiers()
    {
        return array_map(function (Recipient $recipient) {
            return $recipient->getIdentifier();
        }, $this->excludedRecipients);
    }

    /**
     * @param string $class
     *
     * @return array
     */
    public function getExcludedEmailsForEntity($class)
    {
        $filteredRecipients = array_filter($this->excludedRecipients, function (Recipient $recipient) use ($class) {
            return $recipient->getEntity() && $recipient->getEntity()->getClass() === $class;
        });

        return array_map(function (Recipient $recipient) {
            return $recipient->getEmail();
        }, $filteredRecipients);
    }

    /**
     * @param string $class
     *
     * @return array
     */
    public function getExcludedEmailNamesForEntity($class)
    {
        $filteredRecipients = array_filter($this->excludedRecipients, function (Recipient $recipient) use ($class) {
            return $recipient->getEntity() && $recipient->getEntity()->getClass() === $class;
        });

        return array_map(function (Recipient $recipient) {
            return $recipient->getBasicNameWithOrganization();
        }, $filteredRecipients);
    }

    /**
     * @return Organization|null
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
