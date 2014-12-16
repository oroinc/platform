<?php

namespace Oro\Bundle\CommentBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("commentlist")
 * @NamePrefix("oro_api_")
 */
class CommentController extends RestController
{
    /**
     * Get filtered activity lists for given entity
     *
     * @param string  $entityClass Entity class name
     * @param integer $entityId    Entity id
     * @param integer $page        Page number
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      default=1,
     *      nullable=true, description="Page number, starting from 1. Default is 1."
     * )
     * @QueryParam(
     *      name="filter", nullable=true,
     *      description="Array with Activity type and Date range filters values"
     * )
     *
     * @ApiDoc(
     *      description="Returns an array with collection of CommentList objects and count of all records",
     *      resource=true,
     *      statusCodes={
     *          200="Returned when successful",
     *      }
     * )
     * @return JsonResponse
     */
    public function cgetAction($entityClass, $entityId, $page)
    {
        return new JsonResponse([]);
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager()
    {
        // TODO: Implement getManager() method.
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }
}
