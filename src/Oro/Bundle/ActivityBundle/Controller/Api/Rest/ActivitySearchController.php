<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\ChainParameterFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\EntityClassParameterFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("activity_search_relation")
 * @NamePrefix("oro_api_")
 */
class ActivitySearchController extends RestGetController
{
    /**
     * Searches entities associated with the specified type of activity.
     *
     * @param Request $request
     * @param string $activity The type of the activity entity.
     *
     * @Get("/activities/{activity}/relations/search")
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
     *      description="Searches entities associated with the specified type of activity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function cgetAction(Request $request, $activity)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($activity, true));

        $page  = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', self::ITEMS_PER_PAGE);

        $filters = [
            'search' => $request->get('search')
        ];
        $from = $request->get('from', null);
        if ($from) {
            $filter          = new ChainParameterFilter(
                [
                    new StringToArrayParameterFilter(),
                    new EntityClassParameterFilter($this->get('oro_entity.entity_class_name_helper'))
                ]
            );
            $filters['from'] = $filter->filter($from, null);
        }

        return $this->handleGetListRequest($page, $limit, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->container->get('oro_activity.manager.activity_search.api');
    }
}
