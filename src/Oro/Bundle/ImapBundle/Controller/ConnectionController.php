<?php

namespace Oro\Bundle\ImapBundle\Controller;

use FOS\RestBundle\Util\Codes;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Form\Type\MailboxType;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\EmailSettingsType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @return JsonResponse|Response
     */
    public function checkAction(Request $request)
    {
        $responseCode = Codes::HTTP_BAD_REQUEST;

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
            $response = [];
            $password = $this->get('oro_security.encoder.default')->decryptData($origin->getPassword());

            if ($origin->getImapHost() !== null) {
                $response['imap'] = [];

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

                    $entity = $request->get('for_entity', 'user');
                    $organizationId = $request->get('organization');
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
                    $response['imap']['error'] = $e->getMessage();
                }
            }

            if ($origin->getSmtpHost() !== null) {
                $response['smtp'] = [];

                try {
                    /** @var DirectMailer $mailer */
                    $mailer = $this->get('oro_email.direct_mailer');
                    // Prepare Smtp Transport
                    $mailer->prepareSmtpTransport($origin);
                    $transport = $mailer->getTransport();
                    $transport->start();
                } catch (\Exception $e) {
                    $response['smtp']['error'] = $e->getMessage();
                }
            }

            return new JsonResponse($response);
        }

        return new Response('', $responseCode);
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
}
