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

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

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

        $em = $this->getDoctrine()->getManager();
        /** @var NoteRepository $repo */
        $repo = $em->getRepository('OroNoteBundle:Note');
        $qb   = $repo->getAssociatedNotesQueryBuilder($entityClass, $entityId)
            ->orderBy('note.createdAt', $sorting);

        /** @var AclHelper $aclHelper */
        $aclHelper = $this->get('oro_security.acl_helper');
        $query     = $aclHelper->apply($qb);

        /** @var Note[] $items */
        $items = $query->getResult();

        /** @var SecurityFacade $securityFacade */
        $securityFacade = $this->get('oro_security.security_facade');
        /** @var NameFormatter $nameFormatter */
        $nameFormatter = $this->get('oro_locale.formatter.name');

        $result = [];
        foreach ($items as $item) {
            $resultItem = [
                'id'        => $item->getId(),
                'message'   => $item->getMessage(),
                'createdAt' => $item->getCreatedAt()->format('c'),
                'updatedAt' => $item->getCreatedAt()->format('c'),
                'editable'  => $securityFacade->isGranted('EDIT', $item),
                'removable' => $securityFacade->isGranted('DELETE', $item),
            ];
            if ($item->getOwner()) {
                $resultItem['createdBy']          = $nameFormatter->format($item->getOwner());
                $resultItem['createdBy_id']       = $item->getOwner()->getId();
                $resultItem['createdBy_viewable'] = $securityFacade->isGranted('VIEW', $item->getOwner());
            }
            if ($item->getUpdatedBy()) {
                $resultItem['updatedBy']          = $nameFormatter->format($item->getUpdatedBy());
                $resultItem['updatedBy_id']       = $item->getUpdatedBy()->getId();
                $resultItem['updatedBy_viewable'] = $securityFacade->isGranted('VIEW', $item->getUpdatedBy());
            }
            $result[] = $resultItem;
        }

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
