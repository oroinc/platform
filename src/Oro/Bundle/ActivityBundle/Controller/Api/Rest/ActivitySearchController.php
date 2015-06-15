<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivitySearchApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;

/**
 * @RouteResource("search_activity")
 * @NamePrefix("oro_api_")
 */
class ActivitySearchController extends RestGetController
{
    /**
     * Returns the list of activities by the given search string.
     *
     * @param string $entity
     *
     * @Get("/{entity}/activities/search", name="")
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. Defaults to 10."
     * )
     * @QueryParam(
     *     name="search",
     *     requirements=".+",
     *     nullable=true,
     *     description="The search string."
     * )
     * @QueryParam(
     *      name="from",
     *      requirements=".+",
     *      nullable=true,
     *      description="The entity alias. One or several aliases separated by comma. Defaults to all entities"
     * )
     *
     * @ApiDoc(
     *      description="Returns the list of activities by the given search string",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function cgetAction($entity)
    {
        $page  = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        $filters = [
            'search' => $this->getRequest()->get('search')
        ];
        $from    = $this->getRequest()->get('from', null);
        if ($from) {
            $filter          = new StringToArrayParameterFilter();
            $filters['from'] = $filter->filter($from, null);
        }

        $this->getManager()->setClass($entity);

        return $this->handleGetListRequest($page, $limit, $filters);
    }

    /**
     * Gets the API entity manager
     *
     * @return ActivitySearchApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_activity.manager.activity_search.api');
    }
}
