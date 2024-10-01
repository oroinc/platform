<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\EmailBundle\Async\Topic\PurgeEmailAttachmentsTopic;
use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Datagrid\EmailGridHelper;
use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EmailBundle\Form\Handler\EmailHandler;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Manager\EmailNotificationManager;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProvider;
use Oro\Bundle\EmailBundle\Provider\SmtpSettingsProviderInterface;
use Oro\Bundle\EmailBundle\Sync\EmailSynchronizationManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FilterBundle\Filter\FilterBag;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for the email related functionality.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class EmailController extends AbstractController
{
    #[Route(path: '/check-smtp-connection', name: 'oro_email_check_smtp_connection', methods: ['POST'])]
    #[CsrfProtection()]
    public function checkSmtpConnectionAction(Request $request): JsonResponse
    {
        $this->getSmtpSettingsChecker()->checkConnection(
            SmtpSettingsFactory::createFromRequest($request),
            $error
        );

        return new JsonResponse($error ?? '');
    }

    #[Route(path: '/check-saved-smtp-connection', name: 'oro_email_check_saved_smtp_connection', methods: ['GET'])]
    #[CsrfProtection()]
    public function checkSavedSmtpConnectionAction(Request $request): JsonResponse
    {
        $this->getSmtpSettingsChecker()->checkConnection(
            $this->getSmtpSettingsProvider()->getSmtpSettings($this->getScopeIdentifier($request)),
            $error
        );

        return new JsonResponse($error ?? '');
    }

    #[Route(path: '/purge-emails-attachments', name: 'oro_email_purge_emails_attachments', methods: ['POST'])]
    #[AclAncestor('oro_config_system')]
    #[CsrfProtection()]
    public function purgeEmailsAttachmentsAction(): JsonResponse
    {
        $this->getMessageProducer()->send(PurgeEmailAttachmentsTopic::getName(), []);

        return new JsonResponse([
            'message'    => $this->getTranslator()->trans('oro.email.controller.job_scheduled.message'),
            'successful' => true,
        ]);
    }

    /**
     * @param Request $request
     * @param Email $entity
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_email_view', requirements: ['id' => '\d+'])]
    #[Template('@OroEmail/Email/view.html.twig')]
    #[AclAncestor('oro_email_email_view')]
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
     */
    #[Template('@OroEmail/Notification/button.html.twig')]
    public function placeholderLastAction()
    {
        $result = [
            'count' => 0,
            'emails' => []
        ];

        if ($this->isGranted('oro_email_email_user_view')) {
            $currentOrganization = $this->getTokenAccessor()->getOrganization();
            $maxEmailsDisplay = $this->getParameter('oro_email.flash_notification.max_emails_display');
            $emailNotificationManager = $this->getEmailNotificationManager();

            $result = [
                'emails' => json_encode($emailNotificationManager->getEmails(
                    $this->getUser(),
                    $currentOrganization,
                    $maxEmailsDisplay,
                    null
                )),
                'count' => $emailNotificationManager->getCountNewEmails($this->getUser(), $currentOrganization)
            ];
        }

        return $result;
    }

    /**
     * Get last N user emails (N - can be configured by application config)
     * Extra GET params for Route:
     *  "limit" - It defines amount of returned values.
     *  "folderId" -  It defines folder id.
     */
    #[Route(path: '/last', name: 'oro_email_last')]
    #[AclAncestor('oro_email_email_view')]
    public function lastAction(): JsonResponse
    {
        $request = $this->getRequestStack()->getCurrentRequest();
        $maxEmailsDisplay = (int)$request->get('limit');
        $folderId = (int)$request->get('folderId');
        $currentOrganization = $this->getTokenAccessor()->getOrganization();
        if (!$maxEmailsDisplay) {
            $maxEmailsDisplay = $this->getParameter('oro_email.flash_notification.max_emails_display');
        }

        $result = [
            'count' => 0,
            'emails' => []
        ];

        if ($this->isGranted('oro_email_email_user_view')) {
            $emailNotificationManager = $this->getEmailNotificationManager();
            $result = [
                'count' => $emailNotificationManager
                    ->getCountNewEmails($this->getUser(), $currentOrganization, $folderId),
                'emails' => $emailNotificationManager->getEmails(
                    $this->getUser(),
                    $currentOrganization,
                    $maxEmailsDisplay,
                    $folderId
                )];
        }

        return new JsonResponse($result);
    }

    /**
     * @param Email $entity
     * @return array
     */
    #[Route(path: '/view/thread/{id}', name: 'oro_email_thread_view', requirements: ['id' => '\d+'])]
    #[Template('@OroEmail/Email/Thread/view.html.twig')]
    #[AclAncestor('oro_email_email_view')]
    public function viewThreadAction(Email $entity)
    {
        $this->getEmailManager()->setSeenStatus($entity, true, true);

        return ['entity' => $entity];
    }

    /**
     * @param Request $request
     * @param Email $entity
     * @return array
     */
    #[Route(path: '/widget/thread/{id}', name: 'oro_email_thread_widget', requirements: ['id' => '\d+'])]
    #[Template('@OroEmail/Email/widget/thread.html.twig')]
    public function threadWidgetAction(Request $request, Email $entity)
    {
        $emails = [];
        if ($request->get('showSingleEmail', false)) {
            $emails[] = $entity;
        } else {
            $emails = $this->getEmailThreadProvider()->getThreadEmails(
                $this->container->get('doctrine')->getManager(),
                $entity
            );
            $targetActivityClass = $request->get('targetActivityClass');
            $targetActivityId = $request->get('targetActivityId');
            if ($targetActivityClass && $targetActivityId) {
                $emails = $this->getActivityListManager()->filterGroupedEntitiesByActivityLists(
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
            'defaultReplyButton' => $this->getUserConfigManager()->get('oro_email.default_button_reply')
        ];
    }

    /**
     * Used on `My Emails` page to show emails thread with only emails being related to currently logged user.
     *
     *
     * @param Email $entity
     * @return array
     */
    #[Route(path: '/view/user-thread/{id}', name: 'oro_email_user_thread_view', requirements: ['id' => '\d+'])]
    #[Template('@OroEmail/Email/Thread/userEmails.html.twig')]
    #[AclAncestor('oro_email_email_view')]
    public function viewUserThreadAction(Email $entity)
    {
        $this->getEmailManager()->setSeenStatus($entity, true, true);

        return ['entity' => $entity];
    }

    /**
     * Used on `My Emails` page to show emails thread with only emails being related to currently logged user.
     *
     * @param Request $request
     * @param Email $entity
     * @return array
     */
    #[Route(path: '/widget/user-thread/{id}', name: 'oro_email_user_thread_widget', requirements: ['id' => '\d+'])]
    #[Template('@OroEmail/Email/widget/thread.html.twig')]
    public function userThreadWidgetAction(Request $request, Email $entity)
    {
        $emails = [];
        if ($request->get('showSingleEmail', false)) {
            $emails[] = $entity;
        } else {
            $emails = $this->getEmailThreadProvider()->getUserThreadEmails(
                $this->container->get('doctrine')->getManager(),
                $entity,
                $this->getUser(),
                $this->container->get(MailboxManager::class)->findAvailableMailboxes(
                    $this->getUser(),
                    $this->container->get('security.token_storage')->getToken()->getOrganization()
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
            'defaultReplyButton' => $this->getUserConfigManager()->get('oro_email.default_button_reply')
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    #[Route(path: '/view-items', name: 'oro_email_items_view')]
    #[Template('@OroEmail/Email/items.html.twig')]
    public function itemsAction(Request $request)
    {
        $emails = [];
        $ids = $this->prepareArrayParam($request, 'ids');
        if (count($ids) !== 0) {
            $emails = $this->container->get('doctrine')->getRepository(Email::class)->findEmailsByIds($ids);
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
     * @param Request $request
     * @param Email $email
     * @return array
     */
    #[Route(
        path: '/view-group/{id}',
        name: 'oro_email_view_group',
        requirements: ['id' => '\d+'],
        condition: "request !== null && request.get('_widgetContainer')"
    )]
    #[Template('@OroEmail/Email/dialog/viewGroup.html.twig')]
    #[AclAncestor('oro_email_email_view')]
    public function viewGroupAction(Request $request, Email $email)
    {
        $results = $this->getActivityListManager()->getGroupedEntities(
            $email,
            $request->get('targetActivityClass'),
            $request->get('targetActivityId'),
            $request->get('_wid'),
            $this->getFilterBag()->getFilter('datetime')->getMetadata()
        );

        return ['results' => $results];
    }

    /**
     * This action is used to render the list of emails associated with the given entity
     * on the view page of this entity
     *
     *
     *
     * @param string $entityClass
     * @param mixed $entityId
     * @return array
     */
    #[Route(path: '/activity/view/{entityClass}/{entityId}', name: 'oro_email_activity_view')]
    #[Template('@OroEmail/Email/activity.html.twig')]
    public function activityAction($entityClass, $entityId)
    {
        $entity = $this->getEntityRoutingHelper()->getEntity($entityClass, $entityId);
        if (!$this->isGranted('VIEW', $entity)) {
            throw new AccessDeniedException();
        }

        return [
            'entity' => $entity
        ];
    }

    #[Route(
        path: '/create',
        name: 'oro_email_email_create',
        condition: "request !== null && request.get('_widgetContainer')"
    )]
    #[Template('@OroEmail/Email/update.html.twig')]
    #[AclAncestor('oro_email_email_create')]
    public function createAction()
    {
        return $this->process($this->getEmailModelBuilder()->createEmailModel());
    }

    /**
     * @param Email $email
     * @return array
     */
    #[Route(
        path: '/reply/{id}',
        name: 'oro_email_email_reply',
        requirements: ['id' => '\d+'],
        condition: "request !== null && request.get('_widgetContainer')"
    )]
    #[Template('@OroEmail/Email/update.html.twig')]
    #[AclAncestor('oro_email_email_create')]
    public function replyAction(Email $email)
    {
        if (!$this->isGranted('VIEW', $email)) {
            throw new AccessDeniedException();
        }

        return $this->process($this->getEmailModelBuilder()->createReplyEmailModel($email));
    }

    /**
     * @param Email $email
     * @return array
     */
    #[Route(
        path: '/replyall/{id}',
        name: 'oro_email_email_reply_all',
        requirements: ['id' => '\d+'],
        condition: "request !== null && request.get('_widgetContainer')"
    )]
    #[Template('@OroEmail/Email/update.html.twig')]
    #[AclAncestor('oro_email_email_create')]
    public function replyAllAction(Email $email)
    {
        if (!$this->isGranted('VIEW', $email)) {
            throw new AccessDeniedException();
        }

        return $this->process($this->getEmailModelBuilder()->createReplyAllEmailModel($email));
    }

    /**
     * @param Email $email
     * @return array
     */
    #[Route(
        path: '/forward/{id}',
        name: 'oro_email_email_forward',
        requirements: ['id' => '\d+'],
        condition: "request !== null && request.get('_widgetContainer')"
    )]
    #[Template('@OroEmail/Email/update.html.twig')]
    #[AclAncestor('oro_email_email_create')]
    public function forwardAction(Email $email)
    {
        if (!$this->isGranted('VIEW', $email)) {
            throw new AccessDeniedException();
        }

        return $this->process($this->getEmailModelBuilder()->createForwardEmailModel($email));
    }

    /**
     * Get the given email body content
     *
     *
     * @param EmailBody $entity
     * @return Response
     */
    #[Route(path: '/body/{id}', name: 'oro_email_body', requirements: ['id' => '\d+'])]
    #[AclAncestor('oro_email_email_body_view')]
    public function bodyAction(EmailBody $entity)
    {
        return new Response($entity->getBodyContent());
    }

    /**
     * Get a response for download the given email attachment
     *
     *
     * @param EmailAttachment $entity
     * @return Response
     */
    #[Route(path: '/attachment/{id}', name: 'oro_email_attachment', requirements: ['id' => '\d+'])]
    #[AclAncestor('oro_email_email_attachment_view')]
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
     * @param Request $request
     * @param EmailAttachment $attachment
     * @param int $width
     * @param int $height
     * @return Response
     */
    #[Route(
        path: 'media/cache/email_attachment/resize/{id}/{width}/{height}',
        name: 'oro_resize_email_attachment',
        requirements: ['id' => '\d+', 'width' => '\d+', 'height' => '\d+']
    )]
    #[AclAncestor('oro_email_email_attachment_view')]
    public function getResizedAttachmentImageAction(Request $request, EmailAttachment $attachment, $width, $height)
    {
        $path = substr($request->getPathInfo(), 1);
        $fileManager = $this->getFileManager();
        $content = $fileManager->getContent($path, false);
        if (null === $content) {
            $imageBinary = $this->container->get(ResizedImageProvider::class)->getResizedImageByContent(
                ContentDecoder::decode(
                    $attachment->getContent()->getContent(),
                    $attachment->getContent()->getContentTransferEncoding()
                ),
                $width,
                $height
            );

            if ($imageBinary) {
                $content = $imageBinary->getContent();
                $fileManager->writeToStorage($content, $path);
            }
        }

        if (!$content) {
            throw $this->createNotFoundException();
        }

        return new Response($content, Response::HTTP_OK, ['Content-Type' => $attachment->getContentType()]);
    }

    /**
     * Get a zip with email attachments from the given email body
     *
     *
     * @param EmailBody $entity
     * @return BinaryFileResponse
     */
    #[Route(path: '/attachments/{id}', name: 'oro_email_body_attachments', requirements: ['id' => '\d+'])]
    #[AclAncestor('oro_email_email_body_view')]
    public function downloadAttachmentsAction(EmailBody $entity)
    {
        $attachments = $entity->getAttachments();
        if (count($attachments)) {
            $zip = new \ZipArchive();
            $zipName = $this->getFileManager()->getTemporaryFileName('attachments-' . time() . '.zip');
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

        return new Response('', Response::HTTP_NOT_FOUND);
    }

    /**
     * Link attachment to entity
     *
     *
     * @param Request $request
     * @param EmailAttachment $emailAttachment
     * @return JsonResponse
     */
    #[Route(
        path: '/attachment/{id}/link',
        name: 'oro_email_attachment_link',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    #[AclAncestor('oro_email_email_user_edit')]
    #[CsrfProtection()]
    public function linkAction(Request $request, EmailAttachment $emailAttachment)
    {
        try {
            $entity = $this->getTargetEntity($request);
            $this->getEmailAttachmentManager()->linkEmailAttachmentToTargetEntity($emailAttachment, $entity);
            $result = [];
        } catch (\Exception $e) {
            $result = [
                'error' => $e->getMessage()
            ];
        }

        return new JsonResponse($result);
    }

    /**
     *
     * @param Request $request
     *
     * @return array
     */
    #[Route(
        path: '/widget',
        name: 'oro_email_widget_emails',
        condition: "request !== null && request.get('_widgetContainer')"
    )]
    #[Template('@OroEmail/Email/widget/emails.html.twig')]
    public function emailsAction(Request $request)
    {
        return [
            'datagridParameters' => $request->query->all()
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    #[Route(
        path: '/base-widget',
        name: 'oro_email_widget_base_emails',
        condition: "request !== null && request.get('_widgetContainer')"
    )]
    #[Template('@OroEmail/Email/widget/baseEmails.html.twig')]
    public function baseEmailsAction(Request $request)
    {
        return [
            'datagridParameters' => $request->query->all()
        ];
    }

    #[Route(path: '/user-emails', name: 'oro_email_user_emails')]
    #[Template('@OroEmail/Email/userEmails.html.twig')]
    #[AclAncestor('oro_email_email_user_view')]
    public function userEmailsAction()
    {
        return [];
    }

    #[Route(path: '/user-sync-emails', name: 'oro_email_user_sync_emails', methods: ['POST'])]
    #[AclAncestor('oro_email_email_view')]
    #[CsrfProtection()]
    public function userEmailsSyncAction()
    {
        try {
            $this->container->get(EmailSynchronizationManager::class)->syncOrigins(
                $this->container->get(EmailGridHelper::class)->getEmailOrigins($this->getTokenAccessor()->getUserId()),
                true
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => $this->getTranslator()->trans('oro.email.action.message.error')],
                Response::HTTP_OK
            );
        }

        return new JsonResponse([], Response::HTTP_OK);
    }

    /**
     * Togle user emails seen status
     *
     *
     * @param EmailUser $emailUser
     * @return JsonResponse
     */
    #[Route(
        path: '/toggle-seen/{id}',
        name: 'oro_email_toggle_seen',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    #[AclAncestor('oro_email_email_user_edit')]
    #[CsrfProtection()]
    public function toggleSeenAction(EmailUser $emailUser)
    {
        $this->getEmailManager()->toggleEmailUserSeen($emailUser);

        return new JsonResponse(['successful' => true]);
    }

    /**
     * Change email seen status for current user for single email or thread
     *
     *
     * @param Email $email
     * @param string $status
     * @param bool $checkThread if false it will be applied for single email instead of thread
     * @return JsonResponse
     */
    #[Route(
        path: '/mark-seen/{id}/{status}/{checkThread}',
        name: 'oro_email_mark_seen',
        requirements: ['id' => '\d+', 'status' => '\d+', 'checkThread' => '\d+'],
        defaults: ['checkThread' => true],
        methods: ['POST']
    )]
    #[AclAncestor('oro_email_email_user_edit')]
    #[CsrfProtection()]
    public function markSeenAction(Email $email, $status, $checkThread)
    {
        $this->getEmailManager()->setSeenStatus($email, (bool) $status, (bool) $checkThread);

        return new JsonResponse(['successful' => true]);
    }

    /**
     * Mark all user emails as seen
     *
     * @return JsonResponse
     */
    #[Route(path: '/mark_all_as_seen', name: 'oro_email_mark_all_as_seen', methods: ['POST'])]
    #[AclAncestor('oro_email_email_user_edit')]
    #[CsrfProtection()]
    public function markAllEmailsAsSeenAction()
    {
        $result = false;
        $tokenAccessor = $this->getTokenAccessor();
        $loggedUser = $tokenAccessor->getUser();
        if ($loggedUser) {
            $result = $this->getEmailManager()->markAllEmailsAsSeen(
                $loggedUser,
                $tokenAccessor->getOrganization(),
                $this->getRequestStack()->getCurrentRequest()->query->all('ids')
            );
        }

        return new JsonResponse(['successful' => (bool)$result]);
    }

    /**
     *
     * @param Request $request
     * @param string $gridName
     * @param string $actionName
     * @return JsonResponse
     */
    #[Route(path: '/{gridName}/massAction/{actionName}', name: 'oro_email_mark_massaction')]
    #[CsrfProtection()]
    public function markMassAction(Request $request, $gridName, $actionName)
    {
        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->container->get(MassActionDispatcher::class);

        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);

        $data = [
            'successful' => $response->isSuccessful(),
            'message' => $response->getMessage()
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }

    /**
     *
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/autocomplete-recipient', name: 'oro_email_autocomplete_recipient')]
    #[AclAncestor('oro_email_email_create')]
    public function autocompleteRecipientAction(Request $request)
    {
        $query = $request->get('query');
        if ($request->get('search_by_id', false)) {
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
            $organization = $request->get('organization');
            if ($organization) {
                $organization = $this->getOrganizationRepository()->findOneByName($organization);
            }

            $relatedEntity = null;
            $entityClass = $request->get('entityClass');
            $entityId = $request->get('entityId');
            if ($entityClass && $entityId) {
                $em = $this->container->get('doctrine')->getManagerForClass($entityClass);
                $relatedEntity = $em->getReference($entityClass, $entityId);
                if ($relatedEntity === $this->getUser()) {
                    $relatedEntity = null;
                }
            }

            $limit = $request->get('per_page', 100);
            $results = $this->getEmailRecipientsProvider()->getEmailRecipients(
                $relatedEntity,
                $query,
                $organization,
                $limit
            );
        }

        return new JsonResponse(['results' => $results]);
    }

    protected function getEmailRecipientsHelper(): EmailRecipientsHelper
    {
        return $this->container->get(EmailRecipientsHelper::class);
    }

    protected function getEmailRecipientsProvider(): EmailRecipientsProvider
    {
        return $this->container->get(EmailRecipientsProvider::class);
    }

    private function getEmailNotificationManager(): EmailNotificationManager
    {
        return $this->container->get(EmailNotificationManager::class);
    }

    private function getEmailAttachmentManager(): EmailAttachmentManager
    {
        return $this->container->get(EmailAttachmentManager::class);
    }

    protected function getEmailCacheManager(): EmailCacheManager
    {
        return $this->container->get(EmailCacheManager::class);
    }

    protected function getEmailManager(): EmailManager
    {
        return $this->container->get(EmailManager::class);
    }

    protected function getEmailThreadProvider(): EmailThreadProvider
    {
        return $this->container->get(EmailThreadProvider::class);
    }

    private function getEmailModelBuilder(): EmailModelBuilder
    {
        return $this->container->get(EmailModelBuilder::class);
    }

    protected function getOrganizationRepository(): EntityRepository
    {
        return $this->container->get('doctrine')->getRepository(Organization::class);
    }

    private function getMessageProducer(): MessageProducerInterface
    {
        return $this->container->get(MessageProducerInterface::class);
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->container->get(TranslatorInterface::class);
    }

    private function getTokenAccessor(): TokenAccessorInterface
    {
        return $this->container->get(TokenAccessorInterface::class);
    }

    private function getRequestStack(): RequestStack
    {
        return $this->container->get('request_stack');
    }

    private function getFilterBag(): FilterBag
    {
        return $this->container->get(FilterBag::class);
    }

    private function getFileManager(): FileManager
    {
        return $this->container->get(FileManager::class);
    }

    private function getUserConfigManager(): ConfigManager
    {
        return $this->container->get('oro_config.user');
    }

    private function getActivityListManager(): ActivityListManager
    {
        return $this->container->get(ActivityListManager::class);
    }

    private function getEntityRoutingHelper(): EntityRoutingHelper
    {
        return $this->container->get(EntityRoutingHelper::class);
    }

    private function getSmtpSettingsChecker(): SmtpSettingsChecker
    {
        return $this->container->get(SmtpSettingsChecker::class);
    }

    private function getSmtpSettingsProvider(): SmtpSettingsProviderInterface
    {
        return $this->container->get(SmtpSettingsProviderInterface::class);
    }

    private function getEmailHandler(): EmailHandler
    {
        return $this->container->get(EmailHandler::class);
    }

    protected function process(EmailModel $emailModel): array
    {
        $responseData = [
            'entity' => $emailModel,
            'saved' => false,
            'appendSignature' => (bool)$this->getUserConfigManager()->get('oro_email.append_signature')
        ];

        $emailHandler = $this->getEmailHandler();

        $form = $emailHandler->createForm($emailModel);
        $emailHandler->handleRequest($form, $this->getRequestStack()->getCurrentRequest());

        if ($emailHandler->handleFormSubmit($form)) {
            $responseData['saved'] = true;
        }

        $responseData['form'] = $form->createView();

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
        $entityRoutingHelper = $this->getEntityRoutingHelper();
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

    protected function getTargetEntity(Request $request): ?object
    {
        $entityRoutingHelper = $this->getEntityRoutingHelper();
        $targetEntityClass = $entityRoutingHelper->getEntityClassName($request, 'targetActivityClass');
        $targetEntityId = $entityRoutingHelper->getEntityId($request, 'targetActivityId');
        if (!$targetEntityClass || !$targetEntityId) {
            return null;
        }

        return $entityRoutingHelper->getEntity($targetEntityClass, $targetEntityId);
    }

    protected function isAttachmentCreationGranted(Request $request): bool
    {
        $enabledAttachment = false;
        $entityClassName = $this->getEntityRoutingHelper()->getEntityClassName($request, 'targetActivityClass');
        if (null !== $entityClassName) {
            /** @var ConfigProvider $targetConfigProvider */
            $targetConfigProvider = $this->container->get('oro_entity_config.provider.attachment');
            if ($targetConfigProvider->hasConfig($entityClassName)) {
                $enabledAttachment = (bool)$targetConfigProvider->getConfig($entityClassName)->get('enabled');
            }
        }

        return
            $enabledAttachment
            && $this->isGranted(
                'CREATE',
                ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, Attachment::class)
            );
    }

    private function getScopeIdentifier(Request $request): ?object
    {
        $scopeClass = $request->get('scopeClass');
        $scopeId = $request->get('scopeId');
        if (!$scopeClass || !$scopeId) {
            return null;
        }

        return $this->container->get(DoctrineHelper::class)->getEntity($scopeClass, $scopeId);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                SmtpSettingsChecker::class,
                SmtpSettingsProviderInterface::class,
                TranslatorInterface::class,
                TokenAccessorInterface::class,
                EmailNotificationManager::class,
                EmailThreadProvider::class,
                ActivityListManager::class,
                EmailHandler::class,
                EntityRoutingHelper::class,
                EmailModelBuilder::class,
                FileManager::class,
                ResizedImageProvider::class,
                EmailAttachmentManager::class,
                EmailSynchronizationManager::class,
                EmailGridHelper::class,
                MessageProducerInterface::class,
                DoctrineHelper::class,
                MailboxManager::class,
                FilterBag::class,
                MassActionDispatcher::class,
                EmailRecipientsHelper::class,
                EmailRecipientsProvider::class,
                EmailCacheManager::class,
                EmailManager::class,
                'oro_config.user' => ConfigManager::class,
                'oro_entity_config.provider.attachment' => ConfigProvider::class,
                'doctrine' => ManagerRegistry::class,
            ]
        );
    }
}
