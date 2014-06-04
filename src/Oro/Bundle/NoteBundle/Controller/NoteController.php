<?php

namespace Oro\Bundle\NoteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

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

        return array('entity' => $entity);
    }
}
