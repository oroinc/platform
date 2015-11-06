<?php

namespace Oro\Bundle\EntityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;

/**
 * @RouteResource("dictionary_value")
 * @NamePrefix("oro_api_")
 */
class DictionaryController extends RestGetController
{
    /**
     * Get values of a dictionary entity.
     *
     * @param string $dictionary The URL safe name or plural alias of a dictionary entity.
     *
     * @Get("/{dictionary}")
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
     *      description="Number of items per page. Defaults to 10. Set -1 to get all items."
     * )
     * @QueryParam(
     *      name="locale",
     *      requirements=".+",
     *      nullable=true,
     *      description="The preferred locale for dictionary values. Falls back to the default locale."
     * )
     *
     * @ApiDoc(
     *      description="Get values of a dictionary entity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function cgetAction($dictionary)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($dictionary, true));

        $page  = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        return $this->handleGetListRequest($page, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->container->get('oro_entity.manager.dictionary.api');
    }
}
