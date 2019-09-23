<?php

namespace Oro\Bundle\ImapBundle\Controller;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Form\Type\MailboxType;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\EmailSettingsType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Controller for IMAP configuration page
 */
class ConnectionController extends Controller
{
    /**
     * @var ImapEmailFolderManager
     */
    protected $manager;

    /**
     * @Route("/connection/check", name="oro_imap_connection_check", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function checkAction(Request $request)
    {
        $data = null;
        $id = $request->get('id', false);
        if (false !== $id) {
            $data = $this->getDoctrine()->getRepository('OroImapBundle:UserEmailOrigin')->find($id);
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
    public function getFormAction()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $type = $request->get('type');
        $token = $request->get('accessToken');
        $formParentName = $request->get('formParentName');

        $connectionControllerManager = $this->container->get('oro_imap.manager.controller.connection');
        $form = $connectionControllerManager->getImapConnectionForm($type, $token, $formParentName);

        if ($token) {
            $html = $this->renderView('OroImapBundle:Form:accountTypeGmail.html.twig', [
                'form' => $form->createView(),
            ]);
        } else {
            $html = $this->renderView('OroImapBundle:Form:accountTypeOther.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        $response = ['html' => $html];

        return new JsonResponse($response);
    }

    /**
     * @param int|null $id
     *
     * @return Organization|null
     */
    protected function getOrganization($id)
    {
        if (!$id) {
            return null;
        }

        return $this->getDoctrine()->getRepository('OroOrganizationBundle:Organization')->find($id);
    }

    /**
     * @param $origin
     * @param $organization
     *
     * @return mixed
     */
    protected function getFoldersViewForUserMailBox($origin, $organization)
    {
        $user = new User();
        $user->setImapConfiguration($origin);
        $user->setOrganization($organization);
        $userForm = $this->createForm(EmailSettingsType::class);
        $userForm->setData($user);

        return $this->renderView('OroImapBundle:Connection:check.html.twig', [
            'form' => $userForm->createView(),
        ]);
    }

    /**
     * @param $origin
     * @param $organization
     *
     * @return mixed
     */
    protected function getFoldersViewForSystemMailBox($origin, $organization)
    {
        $mailbox = new Mailbox();
        $mailbox->setOrigin($origin);
        if ($organization) {
            $mailbox->setOrganization($organization);
        }
        $mailboxForm = $this->createForm(MailboxType::class);
        $mailboxForm->setData($mailbox);

        return $this->renderView(
            'OroImapBundle:Connection:checkMailbox.html.twig',
            [
                'form' => $mailboxForm->createView(),
            ]
        );
    }

    /**
     * @param FormInterface $form
     *
     * @return array
     */
    private function handleFormErrors(FormInterface $form)
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
                $this->get('translator')->trans('oro.imap.connection.malformed_parameters.error')
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
    private function handleUserEmailOrigin(UserEmailOrigin $origin, string $entity, $organizationId)
    {
        $response = [];

        if ($origin->getImapHost() !== null) {
            $response['imap'] = [];
            $password = $this->get('oro_security.encoder.default')->decryptData($origin->getPassword());

            $config = new ImapConfig(
                $origin->getImapHost(),
                $origin->getImapPort(),
                $origin->getImapEncryption(),
                $origin->getUser(),
                $password
            );

            try {
                $connector = $this->get('oro_imap.connector.factory')->createImapConnector($config);
                $this->manager = new ImapEmailFolderManager(
                    $connector,
                    $this->getDoctrine()->getManager(),
                    $origin
                );

                $emailFolders = $this->manager->getFolders();
                $origin->setFolders($emailFolders);

                $organization = $this->getOrganization($organizationId);
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

                $response['errors'] = $this->get('translator')->trans('oro.imap.connection.retrieve_folders.error');
            }
        }

        if ($origin->getSmtpHost() !== null) {
            $response['smtp'] = [];
        }

        return $response;
    }
}
