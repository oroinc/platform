<?php

namespace Oro\Bundle\NoteBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("note")
 * @NamePrefix("oro_api_")
 */
class NoteController extends RestController implements ClassResourceInterface
{
    /**
     * Get notes for given entity
     *
     * @param Request $request
     * @param string  $entityClass Entity class name
     * @param integer $entityId    Entity id
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
     *      description="Number of items per page. defaults to 10."
     * )
     * @ApiDoc(
     *      description="Get note items",
     *      resource=true
     * )
     * @AclAncestor("oro_note_view")
     * @return Response
     */
    public function cgetAction(Request $request, $entityClass, $entityId)
    {
        $entityClass = $this->get('oro_entity.routing_helper')->resolveEntityClass($entityClass);

        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', self::ITEMS_PER_PAGE);

        /** @var NoteRepository $repo */
        $repo = $this->getManager()->getRepository();
        $qb   = $repo->getAssociatedNotesQueryBuilder($entityClass, $entityId, $page, $limit);

        $result = $qb->getQuery()->getResult();

        $items = array();
        foreach ($result as $item) {
            $items[] = $this->getPreparedItem($item, ['id', 'message', 'createdAt', 'updatedAt', 'owner', 'updatedBy']);
        }
        unset($result);

        return $this->buildResponse($items, self::ACTION_LIST, ['result' => $items, 'query' => $qb]);
    }

    /**
     * Get note
     *
     * @param string $id Note id
     *
     * @ApiDoc(
     *      description="Get note item",
     *      resource=true
     * )
     * @AclAncestor("oro_note_view")
     * @return Response
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Update note
     *
     * @param int $id Note item id
     *
     * @ApiDoc(
     *      description="Update note",
     *      resource=true
     * )
     * @AclAncestor("oro_note_update")
     * @return Response
     */
    public function putAction($id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * Create new note
     *
     * @ApiDoc(
     *      description="Create new note",
     *      resource=true
     * )
     * @AclAncestor("oro_note_create")
     */
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * Delete note
     *
     * @param int $id Note id
     *
     * @ApiDoc(
     *      description="Delete Note",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_note_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroNoteBundle:Note"
     * )
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->get('oro_note.manager.api');
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->get('oro_note.form.note.api');
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        return $this->get('oro_note.form.handler.note_api');
    }

    /**
     * {@inheritdoc}
     */
    protected function transformEntityField($field, &$value)
    {
        switch ($field) {
            case 'owner':
            case 'updatedBy':
                if ($value) {
                    $value = $value->getId();
                }
                break;
            default:
                parent::transformEntityField($field, $value);
        }
    }
}
