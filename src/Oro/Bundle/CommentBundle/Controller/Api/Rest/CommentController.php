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

use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\CommentBundle\Entity\Manager\CommentApiManager;

/**
 * @RouteResource("commentlist")
 * @NamePrefix("oro_api_")
 */
class CommentController extends RestController
{
    /**
     * Get filtered comment for given entity class name and id
     *
     * @param string  $relationClass Entity class name
     * @param integer $relationId    Entity id
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
     * @AclAncestor("oro_comment_view")
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
    public function cgetAction($relationClass, $relationId)
    {
        $page   = $this->getRequest()->get('page', 1);
        $result = $this->getManager()->getCommentList($relationClass, $relationId, $page);

        return new JsonResponse($result);
    }

    /**
     * Get comment
     *
     * @param string $id Comment id
     *
     * @ApiDoc(
     *      description="Get comment item",
     *      resource=true
     * )
     * @AclAncestor("oro_comment_view")
     *
     * @return Response
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Create new comment
     *
     * @param string $relationClass
     * @param string $relationId
     *
     * @ApiDoc(
     *      description="Create new comment",
     *      resource=true
     * )
     *
     * @AclAncestor("oro_comment_create")
     *
     * @return Response
     */
    public function postAction($relationClass, $relationId)
    {
        $entity    = call_user_func_array(array($this, 'createEntity'), func_get_args());
        $exception = $this->getForm();

        $this->getManager()->setRelationField($entity, $relationClass, $relationId);

        $isProcessed = $this->processForm($entity);

        if ($isProcessed) {
            $view = $this->view(
                $this->getManager()->getEntityViewModel($entity, $relationClass, $relationId),
                Codes::HTTP_CREATED
            );
        } else {
            $view = $this->view($exception, Codes::HTTP_BAD_REQUEST);
        }

        return $this->buildResponse($view, self::ACTION_CREATE, ['success' => $isProcessed, 'entity' => $entity]);
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
        $entity = $this->getManager()->find($id);

        if ($entity) {
            if ($this->processForm($entity)) {
                $view = $this->view($this->getManager()->getEntityViewModel($entity), Codes::HTTP_OK);
            } else {
                $view = $this->view($this->getForm(), Codes::HTTP_BAD_REQUEST);
            }
        } else {
            $view = $this->view(null, Codes::HTTP_NOT_FOUND);
        }

        return $this->buildResponse($view, self::ACTION_UPDATE, ['id' => $id, 'entity' => $entity]);
    }

    /**
     * Remove Attachment
     *
     * @param int $id Comment item id
     *
     * @ApiDoc(
     *      description="Remove Attachment",
     *      resource=true
     * )
     * @AclAncestor("oro_comment_update")
     *
     * @return Response
     */
    public function removeAttachmentAction($id)
    {
        $entity = $this->getManager()->find($id);

        if ($entity) {
            $entity->setAttachment(null);
            if ($this->processForm($entity)) {
                $view = $this->view($this->getManager()->getEntityViewModel($entity), Codes::HTTP_OK);
            } else {
                $view = $this->view($this->getForm(), Codes::HTTP_BAD_REQUEST);
            }
        } else {
            $view = $this->view(null, Codes::HTTP_NOT_FOUND);
        }

        return $this->buildResponse($view, self::ACTION_UPDATE, ['id' => $id, 'entity' => $entity]);
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
     * @return CommentApiManager
     */
    public function getManager()
    {
        return $this->get('oro_comment.comment.api_manager');
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        return $this->get('oro_comment.api.form.handler');
    }

    /**
     * {@inheritdoc}
     */
    protected function fixFormData(array &$data, $entity)
    {
        parent::fixFormData($data, $entity);

        unset($data['id']);
        unset($data['owner']);
        unset($data['owner_id']);
        unset($data['editor']);
        unset($data['editor_id']);
        unset($data['relationClass']);
        unset($data['relationId']);
        unset($data['createdAt']);
        unset($data['updatedAt']);
        unset($data['editable']);
        unset($data['removable']);

        return true;
    }
}
