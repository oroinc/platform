<?php

namespace Oro\Bundle\ImapBundle\Controller;

use FOS\RestBundle\Util\Codes;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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

        $form = $this->createForm(
            'oro_imap_configuration',
            null,
            [
                'csrf_protection' => false,
                'validation_groups' => ['Check'],
            ]
        );
        $form->setData($data);
        $form->submit($request);
        /** @var UserEmailOrigin $origin */
        $origin = $form->getData();

        if ($form->isValid() && null !== $origin) {
            $response = [];
            $response['smtp'] = [];
            $response['imap'] = [];

            $password = $this->get('oro_security.encoder.mcrypt')->decryptData($origin->getPassword());
            $config = new ImapConfig(
                $origin->getImapHost(),
                $origin->getImapPort(),
                $origin->getImapEncryption(),
                $origin->getUser(),
                $password
            );

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

            try {
                $connector = $this->get('oro_imap.connector.factory')->createImapConnector($config);
                $this->manager = new ImapEmailFolderManager($connector, $this->getDoctrine()->getManager(), $origin);

                $emailFolders = $this->manager->getFolders();
                $origin->setFolders($emailFolders);

                $user = new User();
                $user->setImapConfiguration($origin);
                $userForm = $this->get('oro_user.form.user');
                $userForm->setData($user);

                $response['imap']['folders'] = $this->renderView('OroImapBundle:Connection:check.html.twig', [
                    'form' => $userForm->createView(),
                ]);
            } catch (\Exception $e) {
                $response['imap']['error'] = $e->getMessage();
            }

            return new JsonResponse($response);
        }

        return new Response('', $responseCode);
    }
}
