<?php

namespace Oro\Bundle\EmailBundle\Model;

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

    /**
     * @param object|null $relatedEntity
     * @param string $query
     * @param int $limit
     */
    public function __construct($relatedEntity, $query, $limit, array $excludedEmails = [])
    {
        $this->relatedEntity = $relatedEntity;
        $this->query = $query;
        $this->limit = $limit;
        $this->excludedEmails = $excludedEmails;
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
}
