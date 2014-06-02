<?php

namespace Oro\Bundle\NoteBundle\Controller;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\NoteBundle\Entity\Note;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route("/note")
 */
class NoteController extends Controller
{
    /**
     * @Route(
     *      "/widget/associates_notes/{entityClass}/{entityId}",
     *      name="oro_note_widget_associated_notes"
     * )
     * @AclAncestor("oro_note_view")
     * @Template
     */
    public function associatedNotesAction($entityClass, $entityId)
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

        $em = $this->getDoctrine()->getManager();
        /** @var EntityRepository $repo */
        $repo  = $em->getRepository('OroNoteBundle:Note');
        $qb = $repo->createQueryBuilder('n')
            ->select('partial n.{id, message, owner, createdAt, updatedBy, updatedAt}')
            ->innerJoin(
                $entityClass,
                'e',
                'WITH',
                sprintf('n.%s = e', ExtendHelper::buildAssociationName($entityClass))
            )
            ->where('e.id = :entity_id')
            ->orderBy('n.updatedBy', 'DESC')
            ->setParameter('entity_id', $entity->getId());

        return [
            'entity' => $entity,
            'notePager' => $this->get('knp_paginator')->paginate($qb, (int) 1, (int) 10)
        ];
    }
}
