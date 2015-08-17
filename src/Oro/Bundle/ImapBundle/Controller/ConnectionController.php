<?php

namespace Oro\Bundle\ImapBundle\Controller;

use FOS\RestBundle\Util\Codes;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;
use Oro\Bundle\UserBundle\Entity\User;

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
        $id   = $request->get('id', false);
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

                    if ($request->get('for_entity', 'user') === 'user') {
                        $user = new User();
                        $user->setImapConfiguration($origin);
                        $userForm = $this->get('oro_user.form.user');
                        $userForm->setData($user);

                        $response['imap']['folders'] = $this->renderView('OroImapBundle:Connection:check.html.twig', [
                            'form' => $userForm->createView(),
                        ]);
                    } elseif ($request->get('for_entity', 'user') === 'mailbox') {
                        $mailbox = new Mailbox();
                        $mailbox->setOrigin($origin);
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
                    $mailer = $this->get('oro_email.direct_mailer');
                    $transport = $mailer->getTransport();
                    $transport->setHost($origin->getSmtpHost());
                    $transport->setPort($origin->getSmtpPort());
                    $transport->setEncryption($origin->getSmtpEncryption());
                    $transport->setUsername($origin->getUser());
                    $transport->setPassword($password);

                    $transport->start();
                } catch (\Exception $e) {
                    $response['smtp']['error'] = $e->getMessage();
                }
            }

            return new JsonResponse($response);
        }

        return new Response('', $responseCode);
    }
}
