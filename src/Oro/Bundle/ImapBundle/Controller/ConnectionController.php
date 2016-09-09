<?php

namespace Oro\Bundle\ImapBundle\Controller;

use FOS\RestBundle\Util\Codes;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class ConnectionController extends Controller
{
    /**
     * @var ImapEmailFolderManager
     */
    protected $manager;

    /**
     * @Route("/connection/check", name="oro_imap_connection_check", methods={"POST"})
     */
    public function checkAction(Request $request)
    {
        $responseCode = Codes::HTTP_BAD_REQUEST;

        $data = null;
        $id = $request->get('id', false);
        if (false !== $id) {
            $data = $this->getDoctrine()->getRepository('OroImapBundle:UserEmailOrigin')->find($id);
        }

        $form = $this->createForm('oro_imap_configuration', null, ['csrf_protection' => false,]);
        $form->setData($data);
        $form->submit($request);
        /** @var UserEmailOrigin $origin */
        $origin = $form->getData();

        if ($form->isValid() && null !== $origin) {
            $response = [];
            $password = $this->get('oro_security.encoder.mcrypt')->decryptData($origin->getPassword());

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
                        $user = new User();
                        $user->setImapConfiguration($origin);
                        $user->setOrganization($organization);
                        $userForm = $this->createForm('oro_user_emailsettings');
                        $userForm->setData($user);

                        $response['imap']['folders'] = $this->renderView('OroImapBundle:Connection:check.html.twig', [
                            'form' => $userForm->createView(),
                        ]);
                    } elseif ($entity === 'mailbox') {
                        $mailbox = new Mailbox();
                        $mailbox->setOrigin($origin);
                        if ($organization) {
                            $mailbox->setOrganization($organization);
                        }
                        $mailboxForm = $this->createForm('oro_email_mailbox');
                        $mailboxForm->setData($mailbox);

                        $response['imap']['folders'] = $this->renderView(
                            'OroImapBundle:Connection:checkMailbox.html.twig',
                            [
                                'form' => $mailboxForm->createView(),
                            ]
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
}
