<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Doctrine\ORM\Query;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Class EmailController
 *
 * @package Oro\Bundle\EmailBundle\Controller
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class EmailController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_email_view", requirements={"id"="\d+"})
     * @Acl(
     *      id="oro_email_view",
     *      type="entity",
     *      class="OroEmailBundle:Email",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function viewAction(Email $entity)
    {
        $templateVars = [
            'entity' => $entity,
            'noBodyFound' => false,
            'canLinkAttachment' => $this->canLinkAttachment(),
            'targetEntityData' => $this->getTargetEntityConfig()
        ];
        try {
            $this->getEmailCacheManager()->ensureEmailBodyCached($entity);
        } catch (LoadEmailBodyException $e) {
            $templateVars['noBodyFound'] = true;
        }
        $this->getEmailManager()->setEmailSeen($entity);

        return $templateVars;
    }

    /**
     * @Route("/view/thread/{id}", name="oro_email_thread_view", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_view")
     * @Template("OroEmailBundle:Email/Thread:view.html.twig")
     */
    public function viewThreadAction(Email $entity)
    {
        return ['entity' => $entity,];
    }

    /**
     * @Route("/widget/thread/{id}", name="oro_email_thread_widget", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_view")
     * @Template("OroEmailBundle:Email/widget:thread.html.twig")
     */
    public function threadWidgetAction(Email $entity)
    {
        $config = $this->get('oro_config.global');
        if ($config->get('oro_email.use_threads_in_emails')) {
            $emails = $this->get('oro_email.email.thread.provider')->getThreadEmails(
                $this->get('doctrine')->getManager(),
                $entity
            );
        } else {
            $emails = [$entity];
        }

        foreach ($emails as $email) {
            try {
                $this->getEmailCacheManager()->ensureEmailBodyCached($email);
            } catch (LoadEmailBodyException $e) {
                // do nothing
            }
        }

        return [
            'entity' => $entity,
            'thread' => $emails,
        ];
    }

    /**
     * @Route("/view-group/{id}", name="oro_email_view_group", requirements={"id"="\d+"})
     * @Acl(
     *      id="oro_email_view",
     *      type="entity",
     *      class="OroEmailBundle:Email",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function viewGroupAction(Email $email)
    {
        $results = $this->get('oro_activity_list.manager')->getGroupedEntities(
            $email,
            $this->getRequest()->get('targetActivityClass'),
            $this->getRequest()->get('targetActivityId'),
            $this->getRequest()->get('_wid'),
            $this->get('oro_filter.datetime_range_filter')->getMetadata()
        );

        return ['results' => $results];
    }


    /**
     * This action is used to render the list of emails associated with the given entity
     * on the view page of this entity
     *
     * @Route(
     *      "/activity/view/{entityClass}/{entityId}",
     *      name="oro_email_activity_view"
     * )
     *
     * @AclAncestor("oro_email_view")
     * @Template
     */
    public function activityAction($entityClass, $entityId)
    {
        return [
            'entity' => $this->get('oro_entity.routing_helper')->getEntity($entityClass, $entityId)
        ];
    }

    /**
     * @Route("/create")
     * @Acl(
     *      id="oro_email_create",
     *      type="entity",
     *      class="OroEmailBundle:Email",
     *      permission="CREATE"
     * )
     * @Template("OroEmailBundle:Email:update.html.twig")
     */
    public function createAction()
    {
        $emailModel = $this->get('oro_email.email.model.builder')->createEmailModel();
        return $this->process($emailModel);
    }

    /**
     * @Route("/reply/{id}", name="oro_email_email_reply", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_create")
     * @Template("OroEmailBundle:Email:update.html.twig")
     */
    public function replyAction(Email $email)
    {
        $emailModel = $this->get('oro_email.email.model.builder')->createReplyEmailModel($email);
        return $this->process($emailModel);
    }

    /**
     * @Route("/forward/{id}", name="oro_email_email_forward", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_create")
     * @Template("OroEmailBundle:Email:update.html.twig")
     */
    public function forwardAction(Email $email)
    {
        $emailModel = $this->get('oro_email.email.model.builder')->createForwardEmailModel($email);
        return $this->process($emailModel);
    }

    /**
     * Get the given email body content
     *
     * @Route("/body/{id}", name="oro_email_body", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_view")
     */
    public function bodyAction(EmailBody $entity)
    {
        return new Response($entity->getBodyContent());
    }

    /**
     * Get a response for download the given email attachment
     *
     * @Route("/attachment/{id}", name="oro_email_attachment", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_view")
     */
    public function attachmentAction(EmailAttachment $entity)
    {
        $response = new Response();
        $response->headers->set('Content-Type', $entity->getContentType());
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $entity->getFileName()));
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $content = ContentDecoder::decode(
            $entity->getContent()->getContent(),
            $entity->getContent()->getContentTransferEncoding()
        );
        $response->setContent($content);

        return $response;
    }

    /**
     * Link attachment to entity
     *
     * @Route("/attachment/{id}/link", name="oro_email_attachment_link", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_view")
     */
    public function linkAction(EmailAttachment $emailAttachment)
    {
        try {
            $entity = $this->getTargetEntity();
            $this->get('oro_email.manager.email_attachment_manager')
                ->linkEmailAttachmentToTargetEntity($emailAttachment, $entity);
            $result = [];
        } catch (\Exception $e) {
            $result = [
                'error' => $e->getMessage()
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/widget", name="oro_email_widget_emails")
     * @Template
     * @AclAncestor("oro_email_view")
     *
     * @param Request $request
     * @return array
     */
    public function emailsAction(Request $request)
    {
        return [
            'datagridParameters' => $request->query->all()
        ];
    }

    /**
     * @Route("/base-widget", name="oro_email_widget_base_emails")
     * @Template
     * @AclAncestor("oro_email_view")
     *
     * @param Request $request
     * @return array
     */
    public function baseEmailsAction(Request $request)
    {
        return [
            'datagridParameters' => $request->query->all()
        ];
    }

    /**
     * @Route("/user-emails", name="oro_email_user_emails")
     * @AclAncestor("oro_email_view")
     * @Template
     */
    public function userEmailsAction()
    {
        return [];
    }

    /**
     * Get email cache manager
     *
     * @return EmailCacheManager
     */
    protected function getEmailCacheManager()
    {
        return $this->container->get('oro_email.email.cache.manager');
    }

    /**
     * Get email cache manager
     *
     * @return EmailManager
     */
    protected function getEmailManager()
    {
        return $this->container->get('oro_email.email.manager');
    }

    /**
     * @param EmailModel $emailModel
     *
     * @return array
     */
    protected function process(EmailModel $emailModel)
    {
        $responseData = [
            'entity' => $emailModel,
            'saved' => false,
            'appendSignature' => (bool)$this->get('oro_config.user')->get('oro_email.append_signature'),
        ];
        if ($this->get('oro_email.form.handler.email')->process($emailModel)) {
            $responseData['saved'] = true;
        }
        $responseData['form'] = $this->get('oro_email.form.email')->createView();

        return $responseData;
    }

    /**
     * Check possibility link attachment to target entity
     *
     * @return bool
     */
    protected function canLinkAttachment()
    {
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $entityClassName = $entityRoutingHelper->getEntityClassName($this->getRequest(), 'targetEntityClass');
        if (null === $entityClassName) {
            return false;
        }

        /** @var ConfigProvider $targetConfigProvider */
        $targetConfigProvider = $this->get('oro_entity_config.provider.attachment');
        $enabledAttachment = (bool)$targetConfigProvider->getConfig($entityClassName)->get('enabled');

        return $enabledAttachment;
    }

    /**
     * Get target entity parameters
     *
     * @return array
     */
    protected function getTargetEntityConfig()
    {
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $targetEntityClass = $entityRoutingHelper->getEntityClassName($this->getRequest(), 'targetEntityClass');
        $targetEntityId = $entityRoutingHelper->getEntityId($this->getRequest(), 'targetEntityId');
        if (null === $targetEntityClass || null === $targetEntityId) {
            return [];
        }
        return [
            'targetEntityClass' => $entityRoutingHelper->encodeClassName($targetEntityClass),
            'targetEntityId' => $targetEntityId
        ];
    }

    /**
     * Get target entity
     *
     * @return object
     */
    protected function getTargetEntity()
    {
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $targetEntityClass = $entityRoutingHelper->getEntityClassName($this->getRequest(), 'targetEntityClass');
        $targetEntityId = $entityRoutingHelper->getEntityId($this->getRequest(), 'targetEntityId');
        $entity = $entityRoutingHelper->getEntity($targetEntityClass, $targetEntityId);

        return $entity;
    }
}
