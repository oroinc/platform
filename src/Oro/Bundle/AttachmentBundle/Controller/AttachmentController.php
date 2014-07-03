<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;

use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class AttachmentController extends Controller
{
    /**
     * @Route(
     *      "attachment/view/widget/{entityClass}/{entityId}",
     *      name="oro_attachment_widget_attachments"
     * )
     *
     * @Template("OroAttachmentBundle:Attachment:attachments.html.twig")
     */
    public function widgetAction($entityClass, $entityId)
    {
        return [
            'entityId'    => $entityId,
            'entityField' => ExtendHelper::buildAssociationName(
                $this->getEntityRoutingHelper()->decodeClassName($entityClass)
            )
        ];
    }

    /**
     * @Route("attachment/create/{entityClass}/{entityId}", name="oro_attachment_create")
     *
     * @Template("OroAttachmentBundle:Attachment:update.html.twig")
     * @ AclAncestor("oro_attachment_create")
     */
    public function createAction($entityClass, $entityId)
    {
        $entityRoutingHelper = $this->getEntityRoutingHelper();

        $entity      = $entityRoutingHelper->getEntity($entityClass, $entityId);
        $entityClass = get_class($entity);

        $attachmentEntity = new Attachment();
        $attachmentEntity->addTarget($entity);

        //$this->getAttachmentManager()->
        //$attachmentEntity->setFile();

        $formAction = $entityRoutingHelper->generateUrl('oro_attachment_create', $entityClass, $entityId);

        return $this->update($attachmentEntity, $formAction);
    }

    /**
     * @param Attachment $entity
     * @param            $formAction
     * @return array
     */
    protected function update(Attachment $entity, $formAction)
    {
        $responseData = [
            'entity' => $entity,
            'saved'  => false
        ];

        if ($this->get('oro_attachment.form.handler.attachment')->process($entity)) {
//            $responseData['saved'] = true;
//            //$responseData['model'] = $this->getAttachmentManager()->getEntityViewModel($entity);
        }

        $responseData['form']       = $this->get('oro_attachment.form.attachment')->createView();
        $responseData['formAction'] = $formAction;

        return $responseData;
    }

    /**
     * @return EntityRoutingHelper
     */
    protected function getEntityRoutingHelper()
    {
        return $this->get('oro_entity.routing_helper');
    }

    /**
     * @return AttachmentManager
     */
    protected function getAttachmentManager()
    {
        return $this->get('oro_attachment.manager');
    }
}
