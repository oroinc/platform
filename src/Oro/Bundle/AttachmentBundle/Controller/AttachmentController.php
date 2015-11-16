<?php

namespace Oro\Bundle\AttachmentBundle\Controller;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

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
     * @Acl(
     *      id="oro_attachment_view",
     *      type="entity",
     *      class="OroAttachmentBundle:Attachment",
     *      permission="VIEW"
     * )
     * @Template("OroAttachmentBundle:Attachment:attachments.html.twig")
     */
    public function widgetAction($entityClass, $entityId)
    {
        $entityClass = $this->get('oro_entity.routing_helper')->resolveEntityClass($entityClass);
        return [
            'entityId'    => $entityId,
            'entityField' => ExtendHelper::buildAssociationName($entityClass),
        ];
    }

    /**
     * @Route("attachment/create/{entityClass}/{entityId}", name="oro_attachment_create")
     *
     * @Template("OroAttachmentBundle:Attachment:update.html.twig")
     * @Acl(
     *      id="oro_attachment_create",
     *      type="entity",
     *      class="OroAttachmentBundle:Attachment",
     *      permission="CREATE"
     * )
     */
    public function createAction($entityClass, $entityId)
    {
        $entityRoutingHelper = $this->getEntityRoutingHelper();

        $entity      = $entityRoutingHelper->getEntity($entityClass, $entityId);
        $entityClass = get_class($entity);

        $attachmentEntity = new Attachment();
        $attachmentEntity->setTarget($entity);

        $form       = $this->createForm(
            $this->container->get('oro_attachment.form.type'),
            $attachmentEntity,
            ['parentEntityClass' => $entityClass, 'checkEmptyFile' => true]
        );

        $formAction = $entityRoutingHelper->generateUrlByRequest(
            'oro_attachment_create',
            $this->getRequest(),
            $entityRoutingHelper->getRouteParameters($entityClass, $entityId)
        );

        return $this->update($form, $formAction);
    }

    /**
     *
     * @Route("attachment/update/{id}", name="oro_attachment_update")
     *
     * @Template("OroAttachmentBundle:Attachment:update.html.twig")
     * @Acl(
     *      id="oro_attachment_update",
     *      type="entity",
     *      class="OroAttachmentBundle:Attachment",
     *      permission="EDIT"
     * )
     */
    public function updateAction(Attachment $attachment)
    {
        $formAction = $this->getRequest()->getUri();
        $form       = $this->createForm(
            $this->container->get('oro_attachment.form.type'),
            $attachment,
            [
                'parentEntityClass' => ClassUtils::getRealClass($attachment->getTarget()),
                'checkEmptyFile'    => false,
                'allowDelete'       => false
            ]
        );
        return $this->update($form, $formAction, true);
    }

    /**
     * @param FormInterface $form
     * @param string        $formAction
     * @param bool          $update
     *
     * @return array
     */
    protected function update(FormInterface $form, $formAction, $update = false)
    {
        $entity       = $form->getData();
        $responseData = [
            'entity' => $entity,
            'saved'  => false
        ];

        if ($update) {
            $responseData['update'] = true;
        }

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
