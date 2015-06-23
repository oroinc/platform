<?php

namespace Oro\Bundle\EmailBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class EmailRecipientsLoadEvent extends Event
{
    const NAME = 'oro_email.email_recipients_load';

    /** @var string */
    protected $query;

    /** @var int */
    protected $limit;

    /** @var array */
    protected $results = [];

    /**
     * @param string $query
     * @param int $limit
     */
    public function __construct($query, $limit)
    {
        $this->query = $query;
        $this->limit = $limit;
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
}
