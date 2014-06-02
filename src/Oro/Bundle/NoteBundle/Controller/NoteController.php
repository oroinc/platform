<?php

namespace Oro\Bundle\NoteBundle\Controller;

use Doctrine\ORM\EntityRepository;

use FOS\Rest\Util\Codes;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NoteBundle\Entity\Note;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/note")
 */
class NoteController extends Controller
{
    /**
     * @Route(
     *      "/widget/notes/{entityClass}/{entityId}",
     *      name="oro_note_widget_notes"
     * )
     *
     * @AclAncestor("oro_note_view")
     * @Template("OroNoteBundle:Note:notes.html.twig")
     */
    public function notesWidgetAction($entityClass, $entityId)
    {
        return [
            'entity' => $this->getTargetEntity($entityClass, $entityId)
        ];
    }

    /**
     * @Route(
     *      "/notes/{entityClass}/{entityId}",
     *      name="oro_note_notes"
     * )
     *
     * @AclAncestor("oro_note_view")
     */
    public function notesAction($entityClass, $entityId)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var EntityRepository $repo */
        $repo = $em->getRepository('OroNoteBundle:Note');
        $qb   = $repo->createQueryBuilder('n')
            ->select('partial n.{id, message, owner, createdAt, updatedBy, updatedAt}, c, u')
            ->innerJoin(
                $entityClass,
                'e',
                'WITH',
                sprintf('n.%s = e', ExtendHelper::buildAssociationName($entityClass))
            )
            ->leftJoin('n.owner', 'c')
            ->leftJoin('n.updatedBy', 'u')
            ->where('e.id = :entity_id')
            ->orderBy('n.updatedBy', 'DESC')
            ->setParameter('entity_id', $entityId);

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
     * @Route(
     *      "/create/{entityClass}/{entityId}",
     *      name="oro_note_create"
     * )
     *
     * @Route("/create", name="oro_note_create")
     * @Template("OroCRMContactBundle:Contact:update.html.twig")
     * @AclAncestor("oro_note_create")
     */
    public function createAction($entityClass, $entityId)
    {
        $targetEntity = $this->getTargetEntity($entityClass, $entityId);

        $entity = new Note();
        $entity->setTargetEntity($targetEntity);

        return $this->update(
            $entity,
            $this->get('router')->generate('oro_note_create', ['entityClass' => $entityClass, 'entityId' => $entityId])
        );
    }

    /**
     * @Route("/update/{id}", name="oro_note_update", requirements={"id"="\d+"})
     *
     * @Template
     * @AclAncestor("oro_note_update")
     */
    public function updateAction(Note $entity)
    {
        return $this->update(
            $entity,
            $this->get('router')->generate('oro_note_update', ['id' => $entity->getId()])
        );
    }

    protected function update(Note $entity, $postRoute)
    {
        $responseData = [
            'entity' => $entity,
            'saved'  => false
        ];

        if ($this->get('oro_note.form.handler.note')->process($entity)) {
            $responseData['saved'] = true;
        }
        $responseData['form']        = $this->get('oro_note.form.type.note')->createView();
        $responseData['form_action'] = $postRoute;

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
