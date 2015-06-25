<?php

namespace Oro\Bundle\EmailBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class EmailRecipientsLoadEvent extends Event
{
    const NAME = 'oro_email.email_recipients_load';

    /** @var object|null */
    protected $relatedEntity;

    /** @var string */
    protected $query;

    /** @var int */
    protected $limit;

    /** @var array */
    protected $results = [];

    /**
     * @param object|null $relatedEntity
     * @param string $query
     * @param int $limit
     */
    public function __construct($relatedEntity, $query, $limit)
    {
        $this->relatedEntity = $relatedEntity;
        $this->query = $query;
        $this->limit = $limit;
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
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getRemainingLimit()
    {
        return $this->limit - count($this->getEmails());
    }

    /**
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param array $results
     */
    public function setResults(array $results)
    {
        $this->results = $results;
    }

    /**
     * @return string[]
     */
    public function getEmails()
    {
        return $this->getEmailsFromData($this->results);
    }

    /**
     * @param string[] $data
     */
    protected function getEmailsFromData(array $data)
    {
        $result = [];
        foreach ($data as $record) {
            if (isset($record['children'])) {
                $result = array_merge($result, $this->getEmailsFromData($record['children']));
            } else {
                $result[] = $record['id'];
            }
        }

        return $result;
    }
}
