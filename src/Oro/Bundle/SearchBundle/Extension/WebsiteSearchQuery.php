<?php

namespace Oro\Bundle\SearchBundle\Extension;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Query;

class WebsiteSearchQuery extends AbstractSearchQuery
{
    /**
     * @var EngineV2Interface
     */
    protected $engine;

    /**
     * @param EngineV2Interface $engine
     * @param Query             $query
     */
    public function __construct(EngineV2Interface $engine, Query $query)
    {
        $this->engine = $engine;
        $this->query  = $query;
    }

    /**
     * No longer needed since we are calling the search engine
     * directly in execute().
     *
     * @deprecated Should be removed from the entire interface
     *
     * @return bool
     */
    public function query()
    {
        throw new \BadMethodCallException('This method should not be called. Use execute() instead');
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return $this->engine->search($this->query);
    }
}
