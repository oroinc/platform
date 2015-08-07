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

    /** @var array */
    protected $excludedEmails;

    /** @var Organization|null */
    protected $organization;

    /**
     * @param object|null $relatedEntity
     * @param string $query
     * @param int $limit
     * @param string[] $excludedEmails
     * @param Organization|null $organization
     */
    public function __construct(
        $relatedEntity,
        $query,
        $limit,
        array $excludedEmails = [],
        Organization $organization = null
    ) {
        $this->relatedEntity = $relatedEntity;
        $this->query = $query;
        $this->limit = $limit;
        $this->excludedEmails = $excludedEmails;
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
    public function getExcludedEmails()
    {
        return $this->excludedEmails;
    }

    /**
     * @return Organization|null
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
