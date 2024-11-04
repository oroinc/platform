<?php

namespace Oro\Bundle\NoteBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for Note entity.
 */
class NoteController extends RestController
{
    /**
     * Get notes for given entity
     *
     * @param Request $request
     * @param string  $entityClass Entity class name
     * @param integer $entityId    Entity id
     *
     * @ApiDoc(
     *      description="Get note items",
     *      resource=true
     * )
     * @return Response
     */
    #[QueryParam(
        name: 'page',
        requirements: '\d+',
        description: 'Page number, starting from 1. Defaults to 1.',
        nullable: true
    )]
    #[QueryParam(
        name: 'limit',
        requirements: '\d+',
        description: 'Number of items per page. defaults to 10.',
        nullable: true
    )]
    #[AclAncestor('oro_note_view')]
    public function cgetAction(Request $request, $entityClass, $entityId)
    {
        $entityClass = $this->container->get('oro_entity.routing_helper')->resolveEntityClass($entityClass);

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
     * @param int $id Note id
     *
     * @ApiDoc(
     *      description="Get note item",
     *      resource=true
     * )
     * @return Response
     */
    #[AclAncestor('oro_note_view')]
    public function getAction(int $id)
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
     * @return Response
     */
    #[AclAncestor('oro_note_update')]
    public function putAction(int $id)
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
     */
    #[AclAncestor('oro_note_create')]
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
     * @return Response
     */
    #[Acl(id: 'oro_note_delete', type: 'entity', class: Note::class, permission: 'DELETE')]
    public function deleteAction(int $id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_note.manager.api');
    }

    /**
     * @return FormInterface
     */
    #[\Override]
    public function getForm()
    {
        return $this->container->get('oro_note.form.note.api');
    }

    /**
     * @return ApiFormHandler
     */
    #[\Override]
    public function getFormHandler()
    {
        return $this->container->get('oro_note.form.handler.note_api');
    }

    #[\Override]
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
