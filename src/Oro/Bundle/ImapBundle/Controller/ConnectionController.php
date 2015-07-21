<?php

namespace Oro\Bundle\ImapBundle\Controller;

use FOS\RestBundle\Util\Codes;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;
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
    public function checkAction()
    {
        $responseCode = Codes::HTTP_BAD_REQUEST;

        $data = null;
        $id   = $this->getRequest()->get('id', false);
        if (false !== $id) {
            $data = $this->getDoctrine()->getRepository('OroImapBundle:ImapEmailOrigin')->find($id);
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
        $form->submit($this->getRequest());
        /** @var ImapEmailOrigin $origin */
        $origin = $form->getData();

        if ($form->isValid() && null !== $origin) {
            $config = new ImapConfig(
                $origin->getHost(),
                $origin->getPort(),
                $origin->getSsl(),
                $origin->getUser(),
                $this->get('oro_security.encoder.mcrypt')->decryptData($origin->getPassword())
            );

            try {
                $connector = $this->get('oro_imap.connector.factory')->createImapConnector($config);
                $this->manager = new ImapEmailFolderManager($connector, $this->getDoctrine()->getManager(), $origin);

                $emailFolders = $this->manager->getFolders();
                $origin->setFolders($emailFolders);

                $user = new User();
                $user->setImapConfiguration($origin);
                $userForm = $this->get('oro_user.form.user');
                $userForm->setData($user);

                return $this->render('OroImapBundle:Connection:check.html.twig', [
                    'form' => $userForm->createView(),
                ]);
            } catch (\Exception $e) {
                $this->get('logger')
                    ->critical('Unable to connect to IMAP server: ' . $e->getMessage(), ['exception' => $e]);
            }
        }

        return new Response('', $responseCode);
    }
}
