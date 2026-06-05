<?php

namespace Oro\Bundle\ImapBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Form\Type\MailboxType;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ImapBundle\Manager\ConnectionControllerManager;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\EmailSettingsType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for IMAP configuration page
 */
class ConnectionController extends AbstractController
{
    /**
     * @Route("/connection/check", name="oro_imap_connection_check", methods={"POST"})
     */
    public function checkAction(Request $request): JsonResponse
    {
        $entity = $request->get('for_entity', 'user');
        if (!$this->isConnectionCheckingGranted($entity, $request)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(
            ConfigurationType::class,
            null,
            ['csrf_protection' => false, 'skip_folders_validation' => true]
        );
        $id = $request->get('id');
        $form->setData($id && is_numeric($id) ? $this->getUserEmailOrigin((int)$id) : null);
        $form->handleRequest($request);
        /** @var UserEmailOrigin $origin */
        $origin = $form->getData();

        if (null !== $origin && $form->isSubmitted() && $form->isValid()) {
            $response = $this->handleUserEmailOrigin($origin, $entity, $this->getOrganizationId($request));
        } else {
            $response = ['errors' => $this->handleFormErrors($form)];
        }

        return new JsonResponse(
            $response,
            !empty($response['errors']) ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK
        );
    }

    /**
     * @Route("imap/connection/account/change", name="oro_imap_change_account_type", methods={"POST"})
     */
    public function getFormAction(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $token = $request->get('accessToken');
        $formParentName = $request->get('formParentName');

        $connectionControllerManager = $this->get(ConnectionControllerManager::class);
        $form = $connectionControllerManager->getImapConnectionForm(
            $type,
            $token,
            $formParentName,
            $request->get('id')
        );

        if ($token) {
            $html = $this->renderView('@OroImap/Form/accountTypeAuthorized.html.twig', [
                'form' => $form->createView(),
            ]);
        } else {
            $html = $this->renderView('@OroImap/Form/accountTypeOther.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        $response = ['html' => $html];

        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @return int|null
     */
    private function getOrganizationId(Request $request)
    {
        $organizationId = $request->query->get('organization');
        if (!$organizationId || !is_numeric($organizationId)) {
            return null;
        }

        return (int)$organizationId;
    }

    private function getOrganization(int $id): ?Organization
    {
        return $this->getEntityManager(Organization::class)->find(Organization::class, $id);
    }

    private function getUserEmailOrigin(int $id): ?UserEmailOrigin
    {
        return $this->getEntityManager(UserEmailOrigin::class)->find(UserEmailOrigin::class, $id);
    }

    /**
     * @param UserEmailOrigin $origin
     * @param int|null $organizationId
     *
     * @return string
     */
    private function getFoldersViewForUserMailbox(UserEmailOrigin $origin, $organizationId)
    {
        $user = new User();
        $user->setImapConfiguration($origin);
        $organization = $organizationId ? $this->getOrganization($organizationId) : null;
        if ($organization) {
            $user->setOrganization($organization);
        }

        $userForm = $this->createForm(EmailSettingsType::class);
        $userForm->setData($user);

        return $this->renderView(
            '@OroImap/Connection/check.html.twig',
            ['form' => $userForm->createView()]
        );
    }

    /**
     * @param UserEmailOrigin $origin
     * @param int|null $organizationId
     * @return string
     */
    private function getFoldersViewForSystemMailbox(UserEmailOrigin $origin, $organizationId)
    {
        $mailbox = new Mailbox();
        $mailbox->setOrigin($origin);
        $organization = $organizationId ? $this->getOrganization($organizationId) : null;
        if ($organization) {
            $mailbox->setOrganization($organization);
        }

        $mailboxForm = $this->createForm(MailboxType::class);
        $mailboxForm->setData($mailbox);

        return $this->renderView(
            '@OroImap/Connection/checkMailbox.html.twig',
            ['form' => $mailboxForm->createView()]
        );
    }

    private function handleFormErrors(FormInterface $form): array
    {
        $nestedErrors = new \SplObjectStorage();
        foreach ($form->getErrors(true) as $error) {
            $nestedErrors->attach($error);
        }

        foreach ($form->getErrors(false) as $error) {
            $nestedErrors->detach($error);
        }

        // Check if there are some nested errors
        if ($nestedErrors->count() || null === $form->getData()) {
            return [
                $this->get(TranslatorInterface::class)->trans('oro.imap.connection.malformed_parameters.error')
            ];
        }

        $errorMessages = [];
        foreach ($form->getErrors() as $error) {
            $cause = $error->getCause();
            if ($cause instanceof ConstraintViolation
                && $cause->getConstraint() instanceof Constraint
            ) {
                $errorMessages[] = $error->getMessage();
            }
        }

        return $errorMessages;
    }

    /**
     * @param UserEmailOrigin $origin
     * @param string $entity
     * @param string|int $organizationId
     *
     * @return array
     */
    private function handleUserEmailOrigin(UserEmailOrigin $origin, string $entity, $organizationId): array
    {
        $response = [];

        if ($origin->getImapHost() !== null) {
            $response['imap'] = [];
            $password = $this->get(DefaultCrypter::class)->decryptData($origin->getPassword());

            $config = new ImapConfig(
                $origin->getImapHost(),
                $origin->getImapPort(),
                $origin->getImapEncryption(),
                $origin->getUser(),
                $password
            );

            $logger = $this->get(LoggerInterface::class);
            $logContext = ['host' => $config->getHost(), 'port' => $config->getPort()];
            $logger->debug('Retrieving IMAP folders ...', $logContext);
            try {
                $connector = $this->get(ImapConnectorFactory::class)->createImapConnector($config);
                $manager = new ImapEmailFolderManager(
                    $connector,
                    $this->getEntityManager(ImapEmailFolder::class),
                    $origin
                );
                $origin->setFolders($manager->getFolders());

                if ('user' === $entity) {
                    $response['imap']['folders'] = $this->getFoldersViewForUserMailbox($origin, $organizationId);
                } elseif ('mailbox' === $entity) {
                    $response['imap']['folders'] = $this->getFoldersViewForSystemMailbox($origin, $organizationId);
                }

                $logger->debug('IMAP folders were successfully retrieved.', $logContext);
            } catch (\Exception $e) {
                $logger->error(
                    'Could not retrieve IMAP folders.',
                    array_merge($logContext, ['exception' => $e])
                );

                $translator = $this->get(TranslatorInterface::class);
                $response['errors'] = $translator->trans('oro.imap.connection.retrieve_folders.error');
            }
        }

        if ($origin->getSmtpHost() !== null) {
            $response['smtp'] = [];
        }

        return $response;
    }

    /**
     * @param string  $entity
     * @param Request $request
     * @return bool
     */
    private function isConnectionCheckingGranted($entity, Request $request)
    {
        if ('user' === $entity) {
            return $this->isConnectionCheckingGrantedForUserMailbox();
        }
        if ('mailbox' === $entity) {
            return $this->isConnectionCheckingGrantedForSystemMailbox($request);
        }
        throw new \LogicException(\sprintf('Unsupported entity: %s.', $entity));
    }

    /**
     * @return bool
     */
    private function isConnectionCheckingGrantedForUserMailbox()
    {
        $user = $this->getTokenAccessor()->getUser();
        if (null === $user) {
            return false;
        }

        return $this->getAuthorizationChecker()->isGranted('CONFIGURE', $user);
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isConnectionCheckingGrantedForSystemMailbox(Request $request)
    {
        $organizationId = $this->getOrganizationId($request);
        $organization = null !== $organizationId
            ? $this->getOrganization($organizationId)
            : $this->getTokenAccessor()->getOrganization();
        if (null === $organization) {
            return false;
        }

        return $this->getAuthorizationChecker()->isGranted(BasicPermission::EDIT, $organization);
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    private function getAuthorizationChecker()
    {
        return $this->get(AuthorizationCheckerInterface::class);
    }

    private function getTokenAccessor(): TokenAccessorInterface
    {
        return $this->get(TokenAccessorInterface::class);
    }

    private function getEntityManager(string $entityClass): EntityManagerInterface
    {
        return $this->getDoctrine()->getManagerForClass($entityClass);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                ImapConnectorFactory::class,
                ConnectionControllerManager::class,
                DefaultCrypter::class,
                LoggerInterface::class,
                AuthorizationCheckerInterface::class,
                TokenAccessorInterface::class,
            ]
        );
    }
}
