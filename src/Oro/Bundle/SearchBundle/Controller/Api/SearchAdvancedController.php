<?php

namespace Oro\Bundle\SearchBundle\Controller\Api;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @RouteResource("search_advanced")
 * @NamePrefix("oro_api_")
 */
class SearchAdvancedController extends FOSRestController
{
    /**
     * Get advanced search result.
     *
     * Supported Keywords:
     *
     *   from: List of entity aliases to search from. It can be one alias or group
     *
     *   where: Auxiliary keyword for visual separation 'from' block from search parameters
     *
     *   and, or: Used to combine multiple clauses, allowing you to refine your search.
     *
     * Syntax: and(or) field_type field_name operator value
     *
     *   offset: Allow to set offset of first result.
     *
     *   max_results: Set results count for the query.
     *
     *   order_by: Allow to set results order. Syntax: order_by field_type field_name direction
     *
     * Supported keywords:
     *
     *  select
     *
     *  text
     *
     *  integer
     *
     *  decimal
     *
     *  datetime
     *
     * Operators:
     *
     *  ~, !~ Work only with string fields. Used for set text field value / search strings without value.
     *
     *  =  Used for search records where field matches the specified value.
     *
     *  != used for search records where field does not matches the specified value.
     *
     *  >, <, <=, >= Operators is used to search for the records that have the specified field must be greater, less,
     * than, less than equals, or greater than equals of the specified value
     *
     *  in Used for search records where field in the specified set of data
     *
     *  !in Used for search records where field not in the specified set of data
     *
     *  replace spaces with _ underscore for fulltext search
     *
     * Examples:
     *
     *  select (name, price) from demo_products
     *
     *  from demo_product where name ~ samsung and double price > 100
     *
     *  where integer count != 10
     *
     *  where all_text !~ test_string
     *
     *  from demo_products where description ~ test order_by name offset 5
     *
     *  from (demo_products, demo_categories) where description ~ test offset 5 max_results 10
     *
     *  integer count !in (1, 3, 5)
     *
     * @ApiDoc(
     *  description="Get advanced search result.",
     *  resource=true,
     *  filters={
     *      {"name"="query", "dataType"="string"}
     *  }
     * )
     * @AclAncestor("oro_search")
     */
    public function getAction()
    {
        $view = new View();

        $result = $this->get('oro_search.index')->advancedSearch(
            $this->getRequest()->get('query')
        );

        $dispatcher = $this->container->get('event_dispatcher');
        foreach ($result->getElements() as $item) {
            $dispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($item));
        }

        return $this->get('fos_rest.view_handler')->handle(
            $view->setData($result->toSearchResultData())
        );
    }
}
