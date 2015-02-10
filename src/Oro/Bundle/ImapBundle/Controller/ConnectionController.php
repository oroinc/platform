<?php

namespace Oro\Bundle\ImapBundle\Controller;

use FOS\RestBundle\Util\Codes;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\ImapBundle\Connector\ImapConfig;

class ConnectionController extends Controller
{
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

        $form = $this->createForm('oro_imap_configuration', $data, ['csrf_protection' => false]);
        $form->submit($this->getRequest());
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
                $connector->getCapability();

                $responseCode = Codes::HTTP_NO_CONTENT;
            } catch (\Exception $e) {
                $this->get('logger')
                    ->critical('Unable to connect to IMAP server: ' . $e->getMessage(), ['exception' => $e]);
            }
        }

        return new Response('', $responseCode);
    }
}
