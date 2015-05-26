<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Doctrine\ORM\Query;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EmailBundle\Tools\EmailHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

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
     * @Template
     */
    public function viewAction(Email $entity)
    {
        $this->assertEmailViewGranted($entity);

        try {
            $this->getEmailCacheManager()->ensureEmailBodyCached($entity);
            $noBodyFound = false;
        } catch (LoadEmailBodyException $e) {
            $noBodyFound = true;
        }

        // set email as seen
        $em = $this->getDoctrine()->getManager();
        $emailUser = $em->getRepository('OroEmailBundle:EmailUser')->findByEmailAndOwner($entity, $this->getUser());
        if ($emailUser) {
            $this->getEmailManager()->setEmailSeen($emailUser);
        }

        return [
            'entity' => $entity,
            'noBodyFound' => $noBodyFound,
            'target' => $this->getTargetEntity($entity),
            'hasGrantReattach' => $this->isAttachmentCreationGranted(),
            'targetEntityData' => $this->getTargetEntityConfig()
        ];
    }

    /**
     * @Route("/view/thread/{id}", name="oro_email_thread_view", requirements={"id"="\d+"})
     * @Template("OroEmailBundle:Email/Thread:view.html.twig")
     */
    public function viewThreadAction(Email $entity)
    {
        $this->assertEmailViewGranted($entity);

        return ['entity' => $entity];
    }

    /**
     * @Route("/widget/thread/{id}", name="oro_email_thread_widget", requirements={"id"="\d+"})
     * @Template("OroEmailBundle:Email/widget:thread.html.twig")
     */
    public function threadWidgetAction(Email $entity)
    {
        $this->assertEmailViewGranted($entity);

        $emails = $this->get('oro_email.email.thread.provider')->getThreadEmails(
            $this->get('doctrine')->getManager(),
            $entity
        );
        $this->loadEmailBody($emails);

        return [
            'entity' => $entity,
            'thread' => $emails,
            'target' => $this->getTargetEntity(),
            'hasGrantReattach' => $this->isAttachmentCreationGranted(),
            'routeParameters' => $this->getTargetEntityConfig(),
            'renderContexts' => $this->getRequest()->get('renderContexts', true)
        ];
    }

    /**
     * @Route("/view-items", name="oro_email_items_view")
     * @Template
     */
    public function itemsAction()
    {
        $emails = [];
        $ids = $this->prepareArrayParam('ids');
        if (count($ids) !== 0) {
            $emails = $this->get('doctrine')->getRepository("OroEmailBundle:Email")->findEmailsByIds($ids);
        }
        $this->loadEmailBody($emails);

        $items = array_filter($emails, [$this->getEmailHelper(), 'isEmailViewGranted']);

        return [
            'items' => $items,
            'target' => $this->getTargetEntity(),
            'hasGrantReattach' => $this->isAttachmentCreationGranted(),
            'routeParameters' => $this->getTargetEntityConfig()
        ];
    }

    /**
     * @Route("/view-group/{id}", name="oro_email_view_group", requirements={"id"="\d+"})
     * @Template
     */
    public function viewGroupAction(Email $email)
    {
        $this->assertEmailViewGranted($email);

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
     * @Template
     */
    public function activityAction($entityClass, $entityId)
    {
        $entity = $this->get('oro_entity.routing_helper')->getEntity($entityClass, $entityId);
        $this->assertEmailViewGranted($entity);

        return [
            'entity' => $entity
        ];
    }

    /**
     * @Route("/create")
     * @AclAncestor("oro_email_email_user_create")
     * @Template("OroEmailBundle:Email:update.html.twig")
     */
    public function createAction()
    {
        $emailModel = $this->get('oro_email.email.model.builder')->createEmailModel();
        return $this->process($emailModel);
    }

    /**
     * @Route("/reply/{id}", name="oro_email_email_reply", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_user_create")
     * @Template("OroEmailBundle:Email:update.html.twig")
     */
    public function replyAction(Email $email)
    {
        $emailModel = $this->get('oro_email.email.model.builder')->createReplyEmailModel($email);
        return $this->process($emailModel);
    }

    /**
     * @Route("/forward/{id}", name="oro_email_email_forward", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_user_create")
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
     */
    public function bodyAction(EmailBody $entity)
    {
        $this->assertEmailViewGranted($entity->getEmail());

        return new Response($entity->getBodyContent());
    }

    /**
     * Get a response for download the given email attachment
     *
     * @Route("/attachment/{id}", name="oro_email_attachment", requirements={"id"="\d+"})
     */
    public function attachmentAction(EmailAttachment $entity)
    {
        $this->assertEmailViewGranted($entity->getEmailBody()->getEmail());

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
     */
    public function linkAction(EmailAttachment $emailAttachment)
    {
        if (!$this->getEmailHelper()->isEmailEditGranted($emailAttachment->getEmailBody()->getEmail())) {
            throw new AccessDeniedException();
        }

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
     * @Template
     */
    public function userEmailsAction()
    {
        return [];
    }

    /**
     * @Route("/context/{id}", name="oro_email_context", requirements={"id"="\d+"})
     * @Template("OroEmailBundle:Email:context.html.twig")
     * @param Email $emailEntity
     * @return array
     */
    public function contextAction(Email $emailEntity)
    {
        $this->assertEmailViewGranted($emailEntity);

        $entityTargets = $this->get('oro_entity.entity_context_provider')->getSupportedTargets($emailEntity);
        return [
            'sourceEntity' => $emailEntity,
            'entityTargets' => $entityTargets,
            'params' => [
                'grid_path' => $this->generateUrl(
                    'oro_email_context_grid',
                    ['activityId' => $emailEntity->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ]
        ];
    }

    /**
     * @Route("/context/grid/{activityId}/{entityClass}", name="oro_email_context_grid")
     * @Template("OroDataGridBundle:Grid:widget/widget.html.twig")
     * @param string $entityClass
     * @param string $activityId
     * @return array
     */
    public function contextGridAction($activityId, $entityClass = null)
    {
        $gridName = $this->get('oro_entity.entity_context_provider')->getContextGridByEntity($entityClass);
        return [
            'gridName' => $gridName,
            'multiselect' => false,
            'params' => ['activityId' => $activityId],
            'renderParams' => []
        ];
    }

    /**
     * @return EmailHelper
     */
    protected function getEmailHelper()
    {
        return $this->get('oro_email.email_helper');
    }

    /**
     * @param Email $entity
     *
     * @throws AccessDeniedException
     */
    protected function assertEmailViewGranted(Email $entity)
    {
        if (!$this->getEmailHelper()->isEmailViewGranted($entity)) {
            throw new AccessDeniedException();
        }
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
     * @param Email[] $emails
     */
    protected function loadEmailBody(array $emails)
    {
        foreach ($emails as $email) {
            try {
                $this->getEmailCacheManager()->ensureEmailBodyCached($email);
            } catch (LoadEmailBodyException $e) {
                // do nothing
            }
        }
    }

    /**
     * @param string $param
     *
     * @return array
     */
    protected function prepareArrayParam($param)
    {
        $result = [];
        $ids = $this->getRequest()->get($param);
        if ($ids) {
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }
            if (is_array($ids)) {
                $result = array_map(
                    function ($id) {
                        return (int)$id;
                    },
                    $ids
                );
            }
        }

        return $result;
    }

    /**
     * Get target entity parameters
     *
     * @param bool $encode
     *
     * @return array
     */
    protected function getTargetEntityConfig($encode = true)
    {
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $targetEntityClass = $entityRoutingHelper->getEntityClassName($this->getRequest(), 'targetActivityClass');
        $targetEntityId = $entityRoutingHelper->getEntityId($this->getRequest(), 'targetActivityId');
        if ($encode) {
            $targetEntityClass = $entityRoutingHelper->encodeClassName($targetEntityClass);
        }
        if (null === $targetEntityClass || null === $targetEntityId) {
            return [];
        }
        return [
            'entityClass' => $targetEntityClass,
            'entityId' => $targetEntityId
        ];
    }

    /**
     * Get target entity
     *
     * @return object|null
     */
    protected function getTargetEntity()
    {
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $targetEntityClass = $entityRoutingHelper->getEntityClassName($this->getRequest(), 'targetActivityClass');
        $targetEntityId = $entityRoutingHelper->getEntityId($this->getRequest(), 'targetActivityId');
        if (!$targetEntityClass || !$targetEntityId) {
            return null;
        }
        return $entityRoutingHelper->getEntity($targetEntityClass, $targetEntityId);
    }

    /**
     * @return bool
     */
    protected function isAttachmentCreationGranted()
    {
        $enabledAttachment = false;
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $entityClassName = $entityRoutingHelper->getEntityClassName($this->getRequest(), 'targetActivityClass');
        if (null !== $entityClassName) {
            /** @var ConfigProvider $targetConfigProvider */
            $targetConfigProvider = $this->get('oro_entity_config.provider.attachment');
            if ($targetConfigProvider->hasConfig($entityClassName)) {
                $enabledAttachment = (bool)$targetConfigProvider->getConfig($entityClassName)->get('enabled');
            }
        }
        $createGrant = $this->get('oro_security.security_facade')
            ->isGranted('CREATE', 'entity:' . 'Oro\Bundle\AttachmentBundle\Entity\Attachment');

        return $enabledAttachment && $createGrant;
    }
}
