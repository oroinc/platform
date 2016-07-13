<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

use FOS\RestBundle\Util\Codes;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProvider;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;

/**
 * Class EmailController
 *
 * @package Oro\Bundle\EmailBundle\Controller
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EmailController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_email_view", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_view")
     * @Template
     */
    public function viewAction(Email $entity)
    {
        try {
            $this->getEmailCacheManager()->ensureEmailBodyCached($entity);
            $noBodyFound = false;
        } catch (LoadEmailBodyException $e) {
            $noBodyFound = true;
        }
        $this->getEmailManager()->setSeenStatus($entity, true);

        return [
            'entity' => $entity,
            'noBodyFound' => $noBodyFound,
            'target' => $this->getTargetEntity($entity),
            'hasGrantReattach' => $this->isAttachmentCreationGranted(),
            'targetEntityData' => $this->getTargetEntityConfig()
        ];
    }

    /**
     * Get new Unread Emails for email notification
     *
     * @Template("OroEmailBundle:Notification:button.html.twig")
     */
    public function placeholderLastAction()
    {
        $currentOrganization = $this->get('oro_security.security_facade')->getOrganization();
        $maxEmailsDisplay = $this->container->getParameter('oro_email.flash_notification.max_emails_display');
        $emailNotificationManager = $this->get('oro_email.manager.notification');

        return [
            'emails' => json_encode($emailNotificationManager->getEmails(
                $this->getUser(),
                $currentOrganization,
                $maxEmailsDisplay,
                null
            )),
            'count'=> $emailNotificationManager->getCountNewEmails($this->getUser(), $currentOrganization)
        ];
    }

    /**
     * Get last N user emails (N - can be configured by application config)
     * Extra GET params for Route:
     *  "limit" - It defines amount of returned values.
     *  "folderId" -  It defines folder id.
     *
     * @Route("/last", name="oro_email_last")
     * @AclAncestor("oro_email_email_view")
     *
     * @return JsonResponse
     */
    public function lastAction()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $maxEmailsDisplay = (int)$request->get('limit');
        $folderId = (int)$request->get('folderId');
        $currentOrganization = $this->get('oro_security.security_facade')->getOrganization();
        if (!$maxEmailsDisplay) {
            $maxEmailsDisplay = $this->container->getParameter('oro_email.flash_notification.max_emails_display');
        }
        $emailNotificationManager = $this->get('oro_email.manager.notification');
        $result = [
            'count' => $emailNotificationManager->getCountNewEmails($this->getUser(), $currentOrganization, $folderId),
            'emails' => $emailNotificationManager->getEmails(
                $this->getUser(),
                $currentOrganization,
                $maxEmailsDisplay,
                $folderId
            )
        ];

        return new JsonResponse($result);
    }

    /**
     * @Route("/view/thread/{id}", name="oro_email_thread_view", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_view")
     * @Template("OroEmailBundle:Email/Thread:view.html.twig")
     */
    public function viewThreadAction(Email $entity)
    {
        $this->getEmailManager()->setSeenStatus($entity, true, true);

        return ['entity' => $entity];
    }

    /**
     * @Route("/widget/thread/{id}", name="oro_email_thread_widget", requirements={"id"="\d+"})
     * @Template("OroEmailBundle:Email/widget:thread.html.twig")
     */
    public function threadWidgetAction(Email $entity)
    {
        $emails = $this->get('oro_email.email.thread.provider')->getThreadEmails(
            $this->get('doctrine')->getManager(),
            $entity
        );
        $emails = array_filter($emails, function ($email) {
            return $this->get('security.context')->isGranted('VIEW', $email);
        });
        $this->loadEmailBody($emails);

        return [
            'entity' => $entity,
            'thread' => $emails,
            'target' => $this->getTargetEntity(),
            'hasGrantReattach' => $this->isAttachmentCreationGranted(),
            'routeParameters' => $this->getTargetEntityConfig(),
            'renderContexts' => $this->getRequest()->get('renderContexts', true),
            'defaultReplyButton' => $this->get('oro_config.user')->get('oro_email.default_button_reply')
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

        $items = array_filter($emails, function ($email) {
            return $this->get('security.context')->isGranted('VIEW', $email);
        });

        return [
            'items' => $items,
            'target' => $this->getTargetEntity(),
            'hasGrantReattach' => $this->isAttachmentCreationGranted(),
            'routeParameters' => $this->getTargetEntityConfig()
        ];
    }

    /**
     * @Route("/view-group/{id}", name="oro_email_view_group", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_view")
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
     * @Template
     */
    public function activityAction($entityClass, $entityId)
    {
        $entity = $this->get('oro_entity.routing_helper')->getEntity($entityClass, $entityId);
        if (!$this->get('oro_security.security_facade')->isGranted('VIEW', $entity)) {
            throw new AccessDeniedException();
        }

        return [
            'entity' => $entity
        ];
    }

    /**
     * @Route("/create", name="oro_email_email_create")
     * @AclAncestor("oro_email_email_create")
     * @Template("OroEmailBundle:Email:update.html.twig")
     */
    public function createAction()
    {
        $emailModel = $this->get('oro_email.email.model.builder')->createEmailModel();
        return $this->process($emailModel);
    }

    /**
     * @Route("/reply/{id}", name="oro_email_email_reply", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_create")
     * @Template("OroEmailBundle:Email:update.html.twig")
     *
     * @param Email $email
     * @return array
     */
    public function replyAction(Email $email)
    {
        $emailModel = $this->get('oro_email.email.model.builder')->createReplyEmailModel($email);
        return $this->process($emailModel);
    }

    /**
     * @Route("/replyall/{id}", name="oro_email_email_reply_all", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_create")
     * @Template("OroEmailBundle:Email:update.html.twig")
     *
     * @param Email $email
     * @return array
     */
    public function replyAllAction(Email $email)
    {
        $emailModel = $this->get('oro_email.email.model.builder')->createReplyAllEmailModel($email);
        return $this->process($emailModel);
    }

    /**
     * @Route("/forward/{id}", name="oro_email_email_forward", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_create")
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
     * @AclAncestor("oro_email_email_body_view")
     */
    public function bodyAction(EmailBody $entity)
    {
        return new Response($entity->getBodyContent());
    }

    /**
     * Get a response for download the given email attachment
     *
     * @Route("/attachment/{id}", name="oro_email_attachment", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_attachment_view")
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
     * Get resized image url
     *
     * @Route("media/cache/email_attachment/resize/{id}/{width}/{height}",
     *   name="oro_resize_email_attachment",
     *   requirements={"id"="\d+", "width"="\d+", "height"="\d+"}
     * )
     * @AclAncestor("oro_email_email_attachment_view")
     *
     * @param EmailAttachment $attachment
     * @param int $width
     * @param int $height
     * @return Response
     */
    public function getResizedAttachmentImageAction(EmailAttachment $attachment, $width, $height)
    {
        $fileSystemMap = $this->get('knp_gaufrette.filesystem_map');
        $fileSystem = $fileSystemMap->get('attachments');
        $path = substr($this->getRequest()->getPathInfo(), 1);
        if (!$fileSystem->has($path)) {
            $filterName = 'attachment_' . $width . '_' . $height;
            $this->get('liip_imagine.filter.configuration')->set(
                $filterName,
                [
                    'filters' => [
                        'thumbnail' => [
                            'size' => [$width, $height]
                        ]
                    ]
                ]
            );
            $content = ContentDecoder::decode(
                $attachment->getContent()->getContent(),
                $attachment->getContent()->getContentTransferEncoding()
            );
            $binary = $this->get('liip_imagine')->load($content);
            $filteredBinary = $this->get('liip_imagine.filter.manager')->applyFilter($binary, $filterName);
            $fileSystem->write($path, $filteredBinary);
        } else {
            $filteredBinary = $fileSystem->read($path);
        }

        $response = new Response($filteredBinary, 200, array('Content-Type' => $attachment->getContentType()));

        return $response;
    }

    /**
     * Get a zip with email attachments from the given email body
     *
     * @Route("/attachments/{id}", name="oro_email_body_attachments", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_body_view")
     *
     * @param EmailBody $entity
     * @return BinaryFileResponse
     */
    public function downloadAttachmentsAction(EmailBody $entity)
    {
        $attachments = $entity->getAttachments();
        if (count($attachments)) {
            $zip = new \ZipArchive();
            $zipName = 'attachments-' . time() . '.zip';
            $zip->open($zipName, \ZipArchive::CREATE);
            foreach ($attachments as $attachment) {
                $content = ContentDecoder::decode(
                    $attachment->getContent()->getContent(),
                    $attachment->getContent()->getContentTransferEncoding()
                );
                $zip->addFromString($attachment->getFileName(), $content);
            }
            $zip->close();

            $response = new BinaryFileResponse($zipName);
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $zipName));
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            $response->setPrivate();
            $response->deleteFileAfterSend(true);

            return $response;
        }

        return new Response('', Codes::HTTP_NOT_FOUND);
    }

    /**
     * Link attachment to entity
     *
     * @Route("/attachment/{id}/link", name="oro_email_attachment_link", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_user_edit")
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
     *
     * @param Request $request
     *
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
     * @AclAncestor("oro_email_email_view")
     * @Template
     */
    public function userEmailsAction()
    {
        return [];
    }

    /**
     * @Route("/user-sync-emails", name="oro_email_user_sync_emails")
     * @AclAncestor("oro_email_email_view")
     */
    public function userEmailsSyncAction()
    {
        try {
            $this->get('oro_email.email_synchronization_manager')->syncOrigins(
                $this->get('oro_email.helper.datagrid.emails')->getEmailOrigins(
                    $this->get('oro_security.security_facade')->getLoggedUserId()
                )
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => $this->get('translator')->trans('oro.email.action.message.error')],
                Codes::HTTP_OK
            );
        }

        return new JsonResponse([], Codes::HTTP_OK);
    }

    /**
     * Togle user emails seen status
     *
     * @Route("/toggle-seen/{id}", name="oro_email_toggle_seen", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_user_edit")
     *
     * @param EmailUser $emailUser
     *
     * @return JsonResponse
     */
    public function toggleSeenAction(EmailUser $emailUser)
    {
        $this->getEmailManager()->toggleEmailUserSeen($emailUser);

        return new JsonResponse(['successful' => true]);
    }

    /**
     * Change email seen status for current user for single email or thread
     *
     * @Route(
     *      "/mark-seen/{id}/{status}/{checkThread}",
     *      name="oro_email_mark_seen",
     *      requirements={"id"="\d+", "status"="\d+", "checkThread"="\d+"},
     *      defaults={"checkThread"=true}
     * )
     * @AclAncestor("oro_email_email_user_edit")
     *
     * @param Email $email
     * @param string $status
     * @param bool $checkThread if false it will be applied for single email instead of thread
     *
     * @return JsonResponse
     */
    public function markSeenAction(Email $email, $status, $checkThread)
    {
        $this->getEmailManager()->setSeenStatus($email, (bool) $status, (bool) $checkThread);

        return new JsonResponse(['successful' => true]);
    }

    /**
     * Mark all user emails as seen
     *
     * @Route("/mark_all_as_seen", name="oro_email_mark_all_as_seen")
     * @AclAncestor("oro_email_email_user_edit")
     * @return JsonResponse
     */
    public function markAllEmailsAsSeenAction()
    {
        $loggedUser = $this->get('oro_security.security_facade')->getLoggedUser();
        $currentOrganization = $this->get('oro_security.security_facade')->getOrganization();
        $ids = $this->container->get('request_stack')->getCurrentRequest()->query->get('ids', []);
        $result = false;

        if ($loggedUser) {
            $result = $this->getEmailManager()->markAllEmailsAsSeen($loggedUser, $currentOrganization, $ids);
        }

        return new JsonResponse(['successful' => (bool)$result]);
    }

    /**
     * @Route("/{gridName}/massAction/{actionName}", name="oro_email_mark_massaction")
     *
     * @param string $gridName
     * @param string $actionName
     *
     * @return JsonResponse
     */
    public function markMassAction($gridName, $actionName)
    {
        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->get('oro_datagrid.mass_action.dispatcher');

        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $this->getRequest());

        $data = [
            'successful' => $response->isSuccessful(),
            'message' => $response->getMessage()
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }

    /**
     * @Route("/autocomplete-recipient", name="oro_email_autocomplete_recipient")
     * @AclAncestor("oro_email_email_create")
     *
     * @return Response
     */
    public function autocompleteRecipientAction(Request $request)
    {
        $query = $request->query->get('query');
        if ($request->query->get('search_by_id', false)) {
            $emails = EmailRecipientsHelper::extractFormRecipientIds($query);
            $results = array_map(function ($email) {
                $recipient = $this->getEmailRecipientsHelper()->createRecipientFromEmail($email);
                if ($recipient) {
                    return $this->getEmailRecipientsHelper()->createRecipientData($recipient);
                }

                return [
                    'id'   => EmailRecipientsHelper::prepareFormRecipientIds($email),
                    'text' => $email,
                ];
            }, $emails);
        } else {
            $organization = $request->query->get('organization');
            if ($organization) {
                $organization = $this->getOrganizationRepository()->findOneByName($organization);
            }

            $relatedEntity = null;
            $entityClass = $request->query->get('entityClass');
            $entityId = $request->query->get('entityId');
            if ($entityClass && $entityId) {
                $em = $this->getEntityManagerForClass($entityClass);
                $relatedEntity = $em->getReference($entityClass, $entityId);
                if ($relatedEntity === $this->getUser()) {
                    $relatedEntity = null;
                }
            }

            $limit = $request->query->get('per_page', 100);
            $results = $this->getEmailRecipientsProvider()->getEmailRecipients(
                $relatedEntity,
                $query,
                $organization,
                $limit
            );
        }

        return new JsonResponse(['results' => $results]);
    }

    /**
     * @return EmailRecipientsHelper
     */
    protected function getEmailRecipientsHelper()
    {
        return $this->get('oro_email.provider.email_recipients.helper');
    }

    /**
     * @return EntityRepository
     */
    protected function getOrganizationRepository()
    {
        return $this->getDoctrine()->getRepository('OroOrganizationBundle:Organization');
    }

    /**
     * @return EmailRecipientsProvider
     */
    protected function getEmailRecipientsProvider()
    {
        return $this->get('oro_email.email_recipients.provider');
    }

    /**
     * @param string $className
     *
     * @return EntityManager
     */
    protected function getEntityManagerForClass($className)
    {
        return $this->getDoctrine()->getManagerForClass($className);
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
            'appendSignature' => (bool)$this->get('oro_config.user')->get('oro_email.append_signature')
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
        /** @var EntityRoutingHelper $entityRoutingHelper */
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $targetEntityClass = $entityRoutingHelper->getEntityClassName($this->getRequest(), 'targetActivityClass');
        $targetEntityId = $entityRoutingHelper->getEntityId($this->getRequest(), 'targetActivityId');
        if ($encode) {
            $targetEntityClass = $entityRoutingHelper->getUrlSafeClassName($targetEntityClass);
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
