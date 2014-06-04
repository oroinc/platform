<?php

namespace Oro\Bundle\NoteBundle\Controller\Api\Soap;

use Symfony\Component\Form\FormInterface;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\EntityBundle\Model\EntityIdSoap;

use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapController;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;

class NoteController extends SoapController
{
    /**
     * @Soap\Method("getNotes")
     *
     * @Soap\Param("page", phpType="int")
     * @Soap\Param("limit", phpType="int")
     * @Soap\Param("entityId", phpType="Oro\Bundle\EntityBundle\Model\EntityIdSoap")
     * @Soap\Result(phpType = "Oro\Bundle\NoteBundle\Entity\NoteSoap[]")
     *
     * @AclAncestor("oro_note_view")
     */
    public function cgetAction(EntityIdSoap $entityId, $page = 1, $limit = 10)
    {
        /** @var NoteRepository $repo */
        $repo = $this->getManager()->getRepository();
        $qb   = $repo->getAssociatedNotesQueryBuilder($entityId->getEntity(), $entityId->getId(), $page, $limit);

        $result = $qb->getQuery()->getResult();

        return $this->transformToSoapEntities($result);
    }

    /**
     * @Soap\Method("getNote")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Result(phpType = "Oro\Bundle\NoteBundle\Entity\NoteSoap")
     * @AclAncestor("oro_note_view")
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * @Soap\Method("createNote")
     * @Soap\Param("note", phpType = "Oro\Bundle\NoteBundle\Entity\NoteSoap")
     * @Soap\Result(phpType = "int")
     * @AclAncestor("oro_note_create")
     */
    public function createAction($note)
    {
        return $this->handleCreateRequest();
    }

    /**
     * @Soap\Method("updateNote")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Param("note", phpType = "Oro\Bundle\NoteBundle\Entity\NoteSoap")
     * @Soap\Result(phpType = "boolean")
     * @AclAncestor("oro_note_update")
     */
    public function updateAction($id, $note)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * @Soap\Method("deleteNote")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Result(phpType = "boolean")
     * @AclAncestor("oro_note_delete")
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_note.manager.api');
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->container->get('oro_note.form.note.api');
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        return $this->container->get('oro_note.form.handler.note_api');
    }

    /**
     * {@inheritDoc}
     */
    protected function fixFormData(array &$data, $entity)
    {
        parent::fixFormData($data, $entity);

        unset($data['id']);
        unset($data['updatedBy']);
        unset($data['createdAt']);
        unset($data['updatedAt']);

        return true;
    }
}
