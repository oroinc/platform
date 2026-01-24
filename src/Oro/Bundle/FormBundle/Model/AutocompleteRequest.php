<?php

namespace Oro\Bundle\FormBundle\Model;

use Symfony\Component\HttpFoundation\Request;

/**
 * Encapsulates autocomplete search request parameters.
 *
 * This class parses and stores autocomplete request data from HTTP requests,
 * including the search handler name, query string, pagination information, and
 * whether the search should be performed by entity ID. It provides convenient
 * accessors for all request parameters.
 */
class AutocompleteRequest
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var int
     */
    protected $perPage;

    /**
     * @var bool
     */
    protected $searchById;

    public function __construct(Request $request)
    {
        $this->createFromRequest($request);
    }

    public function createFromRequest(Request $request)
    {
        $this->name       = $request->get('name');
        $this->query      = $request->get('query');
        $this->page       = intval($request->get('page', 1));
        $this->perPage    = intval($request->get('per_page', 50));
        $this->searchById = (bool)$request->get('search_by_id', false);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * @return boolean
     */
    public function isSearchById()
    {
        return $this->searchById;
    }
}
