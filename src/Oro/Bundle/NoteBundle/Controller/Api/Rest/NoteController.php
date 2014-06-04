<?php

namespace Oro\Bundle\NoteBundle\Controller\Api\Rest;

use FOS\Rest\Util\Codes;
use Oro\Bundle\NoteBundle\Entity\EntityId;
use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;

/**
 * @RouteResource("note")
 * @NamePrefix("oro_api_")
 */
class NoteController extends RestController implements ClassResourceInterface
{
    /**
     * REST GET list
     *
     * @param string  $entityClass
     * @param integer $entityId
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
     *      description="Get all note items",
     *      resource=true
     * )
     * @AclAncestor("oro_note_view")
     * @return Response
     */
    public function cgetAction($entityClass, $entityId)
    {
        $page = (int) $this->getRequest()->get('page', 1);
        $limit = (int) $this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        /** @var NoteRepository $repo */
        $repo = $this->getManager()->getRepository();

        $associationId = new EntityId();
        $associationId
            ->setEntity(str_replace('_', '\\', $entityClass))
            ->setId($entityId);

        $result = $repo->findByAssociatedEntity($associationId, $page, $limit);

        $items = array();
        foreach ($result as $item) {
            $items[] = $this->getPreparedItem($item);
        }
        unset($result);

        return new Response(json_encode($items), Codes::HTTP_OK);
    }

    /**
     * REST GET item
     *
     * @param string $id
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
     * REST PUT
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
     * REST DELETE
     *
     * @param int $id
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
