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
use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\EmailSettingsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
        $data = null;
        $id = $request->get('id', false);
        if (false !== $id) {
            $data = $this->getEntityManager(UserEmailOrigin::class)->find(UserEmailOrigin::class, $id);
        }

        $form = $this->createForm(
            ConfigurationType::class,
            null,
            ['csrf_protection' => false, 'skip_folders_validation' => true]
        );
        $form->setData($data);
        $form->handleRequest($request);
        /** @var UserEmailOrigin $origin */
        $origin = $form->getData();

        if ($form->isSubmitted() && $form->isValid() && null !== $origin) {
            $response = $this->handleUserEmailOrigin(
                $origin,
                $request->get('for_entity', 'user'),
                $request->get('organization')
            );
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

    private function getOrganization(int $id): ?Organization
    {
        return $this->getEntityManager(Organization::class)->find(Organization::class, $id);
    }

    private function getFoldersViewForUserMailBox(UserEmailOrigin $origin, ?Organization $organization): string
    {
        $user = new User();
        $user->setImapConfiguration($origin);
        $user->setOrganization($organization);
        $userForm = $this->createForm(EmailSettingsType::class);
        $userForm->setData($user);

        return $this->renderView(
            '@OroImap/Connection/check.html.twig',
            ['form' => $userForm->createView()]
        );
    }

    private function getFoldersViewForSystemMailBox(UserEmailOrigin $origin, ?Organization $organization): string
    {
        $mailbox = new Mailbox();
        $mailbox->setOrigin($origin);
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

            try {
                $connector = $this->get(ImapConnectorFactory::class)->createImapConnector($config);
                $manager = new ImapEmailFolderManager(
                    $connector,
                    $this->getEntityManager(ImapEmailFolder::class),
                    $origin
                );

                $emailFolders = $manager->getFolders();
                $origin->setFolders($emailFolders);

                $organization = $organizationId
                    ? $this->getOrganization($organizationId)
                    : null;
                if ($entity === 'user') {
                    $response['imap']['folders'] = $this->getFoldersViewForUserMailBox(
                        $origin,
                        $organization
                    );
                } elseif ($entity === 'mailbox') {
                    $response['imap']['folders'] = $this->getFoldersViewForSystemMailBox(
                        $origin,
                        $organization
                    );
                }
            } catch (\Exception $e) {
                $this->get('logger')->error(
                    sprintf('Could not retrieve folders via imap because of "%s"', $e->getMessage())
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
            ]
        );
    }
}
