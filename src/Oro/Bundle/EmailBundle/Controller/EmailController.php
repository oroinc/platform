<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Util\Codes;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProvider;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The controller for the email related functionality.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EmailController extends Controller
{
    /**
     * @Route("/check-smtp-connection", name="oro_email_check_smtp_connection")
     */
    public function checkSmtpConnectionAction(Request $request)
    {
        $smtpSettings = SmtpSettingsFactory::createFromRequest($request);
        $smtpSettingsChecker = $this->get('oro_email.mailer.checker.smtp_settings');

        return new JsonResponse(
            $smtpSettingsChecker->checkConnection($smtpSettings)
        );
    }

    /**
     * @Route("/purge-emails-attachments", name="oro_email_purge_emails_attachments")
     * @AclAncestor("oro_config_system")
     */
    public function purgeEmailsAttachmentsAction()
    {
        $this->getMessageProducer()->send(Topics::PURGE_EMAIL_ATTACHMENTS, []);

        return new JsonResponse([
            'message'    => $this->get('translator')->trans('oro.email.controller.job_scheduled.message'),
            'successful' => true,
        ]);
    }

    /**
     * @Route("/view/{id}", name="oro_email_view", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_view")
     * @Template
     * @param Request $request
     * @param Email $entity
     * @return array
     */
    public function viewAction(Request $request, Email $entity)
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
            'target' => $this->getTargetEntity($request),
            'hasGrantReattach' => $this->isAttachmentCreationGranted($request),
            'targetEntityData' => $this->getTargetEntityConfig($request)
        ];
    }

    /**
     * Get new Unread Emails for email notification
     *
     * @Template("OroEmailBundle:Notification:button.html.twig")
     */
    public function placeholderLastAction()
    {
        $result = [
            'count' => 0,
            'emails' => []
        ];

        if ($this->isGranted('oro_email_email_user_view')) {
            $currentOrganization = $this->get('oro_security.token_accessor')->getOrganization();
            $maxEmailsDisplay = $this->container->getParameter('oro_email.flash_notification.max_emails_display');
            $emailNotificationManager = $this->get('oro_email.manager.notification');

            $result = [
                'emails' => json_encode($this->get('oro_email.manager.notification')->getEmails(
                    $this->getUser(),
                    $currentOrganization,
                    $maxEmailsDisplay,
                    null
                )),
                'count'=> $emailNotificationManager->getCountNewEmails($this->getUser(), $currentOrganization)
            ];
        }

        return $result;
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
        $currentOrganization = $this->get('oro_security.token_accessor')->getOrganization();
        if (!$maxEmailsDisplay) {
            $maxEmailsDisplay = $this->container->getParameter('oro_email.flash_notification.max_emails_display');
        }

        $result = [
            'count' => 0,
            'emails' => []
        ];

        if ($this->isGranted('oro_email_email_user_view')) {
            $emailNotificationManager = $this->get('oro_email.manager.notification');
            $result = [
                'count' => $emailNotificationManager
                    ->getCountNewEmails($this->getUser(), $currentOrganization, $folderId),
                'emails' => $this->get('oro_email.manager.notification')->getEmails(
                    $this->getUser(),
                    $currentOrganization,
                    $maxEmailsDisplay,
                    $folderId
                )];
        }

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
     * @param Request $request
     * @param Email $entity
     * @return array
     */
    public function threadWidgetAction(Request $request, Email $entity)
    {
        $emails = [];
        if ($request->get('showSingleEmail', false)) {
            $emails[] = $entity;
        } else {
            $emails = $this->get('oro_email.email.thread.provider')->getThreadEmails(
                $this->get('doctrine')->getManager(),
                $entity
            );
            $targetActivityClass = $request->get('targetActivityClass');
            $targetActivityId = $request->get('targetActivityId');
            if ($targetActivityClass && $targetActivityId) {
                $emails = $this->get('oro_activity_list.manager')->filterGroupedEntitiesByActivityLists(
                    $emails,
                    $entity,
                    $targetActivityClass,
                    $targetActivityId
                );
            }
        }

        $emails = array_filter($emails, function ($email) {
            return $this->isGranted('VIEW', $email);
        });
        $this->loadEmailBody($emails);

        $this->getEmailManager()->setSeenStatus($entity, true, true);

        return [
            'entity' => $entity,
            'thread' => $emails,
            'target' => $this->getTargetEntity($request),
            'hasGrantReattach' => $this->isAttachmentCreationGranted($request),
            'routeParameters' => $this->getTargetEntityConfig($request),
            'renderContexts' => $request->get('renderContexts', true),
            'defaultReplyButton' => $this->get('oro_config.user')->get('oro_email.default_button_reply')
        ];
    }

    /**
     * Used on `My Emails` page to show emails thread with only emails being related to currently logged user.
     *
     * @Route("/view/user-thread/{id}", name="oro_email_user_thread_view", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_email_view")
     * @Template("OroEmailBundle:Email/Thread:userEmails.html.twig")
     */
    public function viewUserThreadAction(Email $entity)
    {
        $this->getEmailManager()->setSeenStatus($entity, true, true);

        return ['entity' => $entity];
    }

    /**
     * Used on `My Emails` page to show emails thread with only emails being related to currently logged user.
     *
     * @Route("/widget/user-thread/{id}", name="oro_email_user_thread_widget", requirements={"id"="\d+"})
     * @Template("OroEmailBundle:Email/widget:thread.html.twig")
     * @param Request $request
     * @param Email $entity
     * @return array
     */
    public function userThreadWidgetAction(Request $request, Email $entity)
    {
        $emails = [];
        if ($request->get('showSingleEmail', false)) {
            $emails[] = $entity;
        } else {
            $emails = $this->get('oro_email.email.thread.provider')->getUserThreadEmails(
                $this->get('doctrine')->getManager(),
                $entity,
                $this->getUser(),
                $this->get('oro_email.mailbox.manager')->findAvailableMailboxes(
                    $this->getUser(),
                    $this->get('security.token_storage')->getToken()->getOrganizationContext()
                )
            );
        }

        $emails = array_filter($emails, function ($email) {
            return $this->isGranted('VIEW', $email);
        });
        $this->loadEmailBody($emails);

        return [
            'entity' => $entity,
            'thread' => $emails,
            'target' => $this->getTargetEntity($request),
            'hasGrantReattach' => $this->isAttachmentCreationGranted($request),
            'routeParameters' => $this->getTargetEntityConfig($request),
            'renderContexts' => $request->get('renderContexts', true),
            'defaultReplyButton' => $this->get('oro_config.user')->get('oro_email.default_button_reply')
        ];
    }

    /**
     * @Route("/view-items", name="oro_email_items_view")
     * @Template
     * @param Request $request
     * @return array
     */
    public function itemsAction(Request $request)
    {
        $emails = [];
        $ids = $this->prepareArrayParam($request, 'ids');
        if (count($ids) !== 0) {
            $emails = $this->get('doctrine')->getRepository("OroEmailBundle:Email")->findEmailsByIds($ids);
        }
        $this->loadEmailBody($emails);

        $items = array_filter($emails, function ($email) {
            return $this->isGranted('VIEW', $email);
        });

        return [
            'items' => $items,
            'target' => $this->getTargetEntity($request),
            'hasGrantReattach' => $this->isAttachmentCreationGranted($request),
            'routeParameters' => $this->getTargetEntityConfig($request)
        ];
    }

    /**
     * @Route(
     *     "/view-group/{id}",
     *     name="oro_email_view_group",
     *     requirements={"id"="\d+"},
     *     condition="request !== null && request.get('_widgetContainer')"
     * )
     * @AclAncestor("oro_email_email_view")
     * @Template
     * @param Request $request
     * @param Email $email
     * @return array
     */
    public function viewGroupAction(Request $request, Email $email)
    {
        $results = $this->get('oro_activity_list.manager')->getGroupedEntities(
            $email,
            $request->get('targetActivityClass'),
            $request->get('targetActivityId'),
            $request->get('_wid'),
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
        if (!$this->isGranted('VIEW', $entity)) {
            throw new AccessDeniedException();
        }

        return [
            'entity' => $entity
        ];
    }

    /**
     * @Route(
     *     "/create",
     *     name="oro_email_email_create",
     *     condition="request !== null && request.get('_widgetContainer')"
     * )
     * @AclAncestor("oro_email_email_create")
     * @Template("OroEmailBundle:Email:update.html.twig")
     */
    public function createAction()
    {
        $emailModel = $this->get('oro_email.email.model.builder')->createEmailModel();
        return $this->process($emailModel);
    }

    /**
     * @Route(
     *     "/reply/{id}", name="oro_email_email_reply",
     *     requirements={"id"="\d+"},
     *     condition="request !== null && request.get('_widgetContainer')"
     * )
     * @AclAncestor("oro_email_email_create")
     * @Template("OroEmailBundle:Email:update.html.twig")
     *
     * @param Email $email
     * @return array
     */
    public function replyAction(Email $email)
    {
        if (!$this->isGranted('VIEW', $email)) {
            throw new AccessDeniedException();
        }
        $emailModel = $this->get('oro_email.email.model.builder')->createReplyEmailModel($email);
        return $this->process($emailModel);
    }

    /**
     * @Route(
     *     "/replyall/{id}",
     *     name="oro_email_email_reply_all",
     *     requirements={"id"="\d+"},
     *     condition="request !== null && request.get('_widgetContainer')"
     * )
     * @AclAncestor("oro_email_email_create")
     * @Template("OroEmailBundle:Email:update.html.twig")
     *
     * @param Email $email
     * @return array
     */
    public function replyAllAction(Email $email)
    {
        if (!$this->isGranted('VIEW', $email)) {
            throw new AccessDeniedException();
        }
        $emailModel = $this->get('oro_email.email.model.builder')->createReplyAllEmailModel($email);
        return $this->process($emailModel);
    }

    /**
     * @Route(
     *     "/forward/{id}",
     *     name="oro_email_email_forward",
     *     requirements={"id"="\d+"},
     *     condition="request !== null && request.get('_widgetContainer')"
     * )
     * @AclAncestor("oro_email_email_create")
     * @Template("OroEmailBundle:Email:update.html.twig")
     */
    public function forwardAction(Email $email)
    {
        if (!$this->isGranted('VIEW', $email)) {
            throw new AccessDeniedException();
        }
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
     * @param Request $request
     * @param EmailAttachment $attachment
     * @param int $width
     * @param int $height
     * @return Response
     */
    public function getResizedAttachmentImageAction(Request $request, EmailAttachment $attachment, $width, $height)
    {
        $path = substr($request->getPathInfo(), 1);
        $fileManager = $this->get('oro_attachment.file_manager');
        $content = $fileManager->getContent($path, false);
        if (null === $content) {
            $thumbnail = $this->get('oro_attachment.thumbnail_factory')->createThumbnail(
                ContentDecoder::decode(
                    $attachment->getContent()->getContent(),
                    $attachment->getContent()->getContentTransferEncoding()
                ),
                $width,
                $height
            );
            $content = $thumbnail->getBinary()->getContent();
            $fileManager->writeToStorage($content, $path);
        }

        return new Response($content, Response::HTTP_OK, ['Content-Type' => $attachment->getContentType()]);
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
            $fileManager = $this->get('oro_attachment.file_manager');
            $zipName = $fileManager->getTemporaryFileName('attachments-' . time() . '.zip');
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
     * @param Request $request
     * @param EmailAttachment $emailAttachment
     * @return JsonResponse
     */
    public function linkAction(Request $request, EmailAttachment $emailAttachment)
    {
        try {
            $entity = $this->getTargetEntity($request);
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
     * @Route(
     *     "/widget",
     *     name="oro_email_widget_emails",
     *     condition="request !== null && request.get('_widgetContainer')"
     * )
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
     * @Route(
     *     "/base-widget",
     *     name="oro_email_widget_base_emails",
     *     condition="request !== null && request.get('_widgetContainer')"
     * )
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
     * @AclAncestor("oro_email_email_user_view")
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
                    $this->get('oro_security.token_accessor')->getUserId()
                ),
                true
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
        $loggedUser = $this->get('oro_security.token_accessor')->getUser();
        $currentOrganization = $this->get('oro_security.token_accessor')->getOrganization();
        $ids = $this->container->get('request_stack')->getCurrentRequest()->query->get('ids', []);
        $result = false;

        if ($loggedUser) {
            $result = $this->getEmailManager()->markAllEmailsAsSeen($loggedUser, $currentOrganization, $ids);
        }

        return new JsonResponse(['successful' => (bool)$result]);
    }

    /**
     * @Route("/{gridName}/massAction/{actionName}", name="oro_email_mark_massaction")
     * @param Request $request
     * @param string $gridName
     * @param string $actionName
     *
     * @return JsonResponse
     */
    public function markMassAction(Request $request, $gridName, $actionName)
    {
        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->get('oro_datagrid.mass_action.dispatcher');

        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);

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
     * @param Request $request
     * @param string $param
     *
     * @return array
     */
    protected function prepareArrayParam(Request $request, $param)
    {
        $result = [];
        $ids = $request->get($param);
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
     * @param Request $request
     * @param bool $encode
     *
     * @return array
     */
    protected function getTargetEntityConfig(Request $request, $encode = true)
    {
        /** @var EntityRoutingHelper $entityRoutingHelper */
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $targetEntityClass = $entityRoutingHelper->getEntityClassName($request, 'targetActivityClass');
        $targetEntityId = $entityRoutingHelper->getEntityId($request, 'targetActivityId');
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
     * @param Request $request
     * @return object|null
     */
    protected function getTargetEntity(Request $request)
    {
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $targetEntityClass = $entityRoutingHelper->getEntityClassName($request, 'targetActivityClass');
        $targetEntityId = $entityRoutingHelper->getEntityId($request, 'targetActivityId');
        if (!$targetEntityClass || !$targetEntityId) {
            return null;
        }
        return $entityRoutingHelper->getEntity($targetEntityClass, $targetEntityId);
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function isAttachmentCreationGranted(Request $request)
    {
        $enabledAttachment = false;
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $entityClassName = $entityRoutingHelper->getEntityClassName($request, 'targetActivityClass');
        if (null !== $entityClassName) {
            /** @var ConfigProvider $targetConfigProvider */
            $targetConfigProvider = $this->get('oro_entity_config.provider.attachment');
            if ($targetConfigProvider->hasConfig($entityClassName)) {
                $enabledAttachment = (bool)$targetConfigProvider->getConfig($entityClassName)->get('enabled');
            }
        }
        $createGrant = $this->isGranted('CREATE', 'entity:' . 'Oro\Bundle\AttachmentBundle\Entity\Attachment');

        return $enabledAttachment && $createGrant;
    }

    /**
     * @return MessageProducer
     */
    private function getMessageProducer()
    {
        return $this->get('oro_message_queue.message_producer');
    }
}
