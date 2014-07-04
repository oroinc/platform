<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Oro\Bundle\AttachmentBundle\Form\Type\AttachmentType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
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
        $entityClass = $this->get('oro_entity.routing_helper')->decodeClassName($entityClass);
        return [
            'entityId' => $entityId,
            'entityField' => ExtendHelper::buildAssociationName($entityClass),
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

        $entity = $entityRoutingHelper->getEntity($entityClass, $entityId);
        $entityClass = get_class($entity);

        $attachmentEntity = new Attachment();
        $attachmentEntity->setTarget($entity);

        $formAction = $entityRoutingHelper->generateUrl('oro_attachment_create', $entityClass, $entityId);

        return $this->update($attachmentEntity, $formAction, $entityClass);
    }

    /**
     * @param Attachment $entity
     * @param string     $formAction
     * @param string     $entityClass
     * @return array
     */
    protected function update(Attachment $entity, $formAction, $entityClass)
    {
        $responseData = [
            'entity' => $entity,
            'saved' => false
        ];
        $form = $this->createForm(new AttachmentType(), $entity, ['parentEntityClass' => $entityClass]);

        if ($this->get('oro_attachment.form.handler.attachment')->process($form)) {
            $responseData['saved'] = true;
        } else {
            $responseData['form']       = $form->createView();
            $responseData['formAction'] = $formAction;
        }

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
