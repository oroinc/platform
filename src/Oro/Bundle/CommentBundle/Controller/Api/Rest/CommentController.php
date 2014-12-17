<?php

namespace Oro\Bundle\CommentBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

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
    public function cgetAction($entityClass, $entityId)
    {
        $result = $this->getManager()->getCommentList($entityClass, $entityId);

        return new JsonResponse($result);
    }

    /**
     * Get note
     *
     * @param string $id Comment id
     *
     * @ApiDoc(
     *      description="Get comment item",
     *      resource=true
     * )
     * @AclAncestor("oro_comment_view")
     * @return Response
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Create new comment
     *
     * @ApiDoc(
     *      description="Create new comment",
     *      resource=true
     * )
     * @AclAncestor("oro_comment_create")
     */
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * Update comment
     *
     * @param int $id Comment item id
     *
     * @ApiDoc(
     *      description="Update comment",
     *      resource=true
     * )
     * @AclAncestor("oro_comment_update")
     *
     * @return Response
     */
    public function putAction($id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * Delete Comment
     *
     * @param int $id comment id
     *
     * @ApiDoc(
     *      description="Delete Comment",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_comment_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroCommentBundle:Comment"
     * )
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->get('oro_comment.form.comment.api');
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->get('oro_comment.comment.manager');
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        return $this->get('oro_comment.api.form.handler');
    }
}
