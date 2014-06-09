<?php

namespace Oro\Bundle\NoteBundle\Controller;

use FOS\Rest\Util\Codes;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Entity\Manager\EntityManager;

/**
 * @Route("/notes")
 */
class NoteController extends Controller
{
    /**
     * @Route(
     *      "/view/widget/{entityClass}/{entityId}",
     *      name="oro_note_widget_notes"
     * )
     *
     * @AclAncestor("oro_note_view")
     * @Template("OroNoteBundle:Note:notes.html.twig")
     */
    public function widgetAction($entityClass, $entityId)
    {
        $entityClass = str_replace('_', '\\', $entityClass);

        return [
            'entity' => $this->getTargetEntity($entityClass, $entityId)
        ];
    }

    /**
     * @Route(
     *      "/view/{entityClass}/{entityId}",
     *      name="oro_note_notes"
     * )
     *
     * @AclAncestor("oro_note_view")
     */
    public function getAction($entityClass, $entityId)
    {
        $entityClass = str_replace('_', '\\', $entityClass);

        $sorting = strtoupper($this->getRequest()->get('sorting', 'DESC'));

        /** @var EntityManager $securityFacade */
        $manager = $this->get('oro_note.manager');

        $result = $manager->getEntityViewModels(
            $manager->getList($entityClass, $entityId, $sorting)
        );

        return new Response(json_encode($result), Codes::HTTP_OK);
    }

    /**
     * @Route("/create/{entityClass}/{entityId}", name="oro_note_create")
     *
     * @Template("OroNoteBundle:Note:update.html.twig")
     * @AclAncestor("oro_note_create")
     */
    public function createAction($entityClass, $entityId)
    {
        $entityClass = str_replace('_', '\\', $entityClass);

        $targetEntity = $this->getTargetEntity($entityClass, $entityId);

        $entity = new Note();
        $entity->setTarget($targetEntity);

        $formAction = $this->get('router')->generate(
            'oro_note_create',
            ['entityClass' => str_replace('\\', '_', $entityClass), 'entityId' => $entityId]
        );

        return $this->update($entity, $formAction);
    }

    /**
     * @Route("/update/{id}", name="oro_note_update", requirements={"id"="\d+"})
     *
     * @Template
     * @AclAncestor("oro_note_update")
     */
    public function updateAction(Note $entity)
    {
        $formAction = $this->get('router')->generate('oro_note_update', ['id' => $entity->getId()]);

        return $this->update($entity, $formAction);
    }

    protected function update(Note $entity, $formAction)
    {
        $responseData = [
            'entity' => $entity,
            'saved'  => false
        ];

        if ($this->get('oro_note.form.handler.note')->process($entity)) {
            $responseData['saved'] = true;
            /** @var EntityManager $securityFacade */
            $manager               = $this->get('oro_note.manager');
            $responseData['model'] = $manager->getEntityViewModel($entity);
        }
        $responseData['form']       = $this->get('oro_note.form.note')->createView();
        $responseData['formAction'] = $formAction;

        return $responseData;
    }

    /**
     * @param string $entityClass
     * @param string $entityId
     *
     * @return object
     *
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    protected function getTargetEntity($entityClass, $entityId)
    {
        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->get('oro_entity.doctrine_helper');

        $entity = null;
        try {
            $entity = $doctrineHelper->getEntity($entityClass, $entityId);
        } catch (NotManageableEntityException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
        if (!$entity) {
            throw $this->createNotFoundException();
        }

        return $entity;
    }
}
