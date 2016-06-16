<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Doctrine\ORM\Proxy\Proxy;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;

/**
 * @RouteResource("email_activity_relation")
 * @NamePrefix("oro_api_")
 */
class EmailActivityEntityController extends RestGetController
{
    /**
     * Get entities associated with the email activity.
     *
     * @param int $id The id of the email entity.
     *
     * @Get("/activities/emails/{id}/relations", name="")
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
     *      name="skip_custom_entity",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Option to hide custom entities in the results. Available values  0 and 1. Defaults to 0."
     * )
     *
     * @ApiDoc(
     *      description="Get entities associated with the email activity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function cgetAction($id)
    {
        $page  = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        $filters = [
            'skip_custom_entity' => (bool)$this->getRequest()->get('skip_custom_entity', 0),
            'criteria' => $this->buildFilterCriteria(['id' => ['=', $id]])
        ];

        return $this->handleGetListRequest($page, $limit, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->container->get('oro_email.manager.email_activity_entity.api');
    }

    /**
     * {@inheritdoc}
     */
    protected function getPreparedItem($entity, $resultFields = [])
    {
        if ($entity instanceof Proxy && !$entity->__isInitialized()) {
            $entity->__load();
        }
        $result = parent::getPreparedItem($entity, $resultFields);
        if ($entity && is_array($entity)) {
            $result = $this->addRouteView($result);
        }

        return $result;
    }

    /**
     * @param $result
     *
     * @return mixed
     */
    protected function addRouteView($result)
    {
        $metadata = $this->get('oro_entity_config.config_manager')->getEntityMetadata($result['entity']);
        if ($metadata) {
            $result['urlView'] =
                $this->get('router')->generate($metadata->getRoute(), ['id' => $result['id']]);
        } else {
            $result['urlView'] = '';
        }

        return $result;
    }
}
