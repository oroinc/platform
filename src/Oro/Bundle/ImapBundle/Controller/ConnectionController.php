<?php

namespace Oro\Bundle\ImapBundle\Controller;

use FOS\RestBundle\Util\Codes;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Mail\Storage\Folder;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;

class ConnectionController extends Controller
{
    /**
     * @var ImapEmailManager
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
                $this->manager = new ImapEmailManager($connector);

                $connector->getCapability();

                $folders = $this->syncFolders();
                $emailFolderForm = $this->createForm('oro_email_email_folder_tree');
                $emailFolderForm->setData($folders);

                return $this->render('OroImapBundle:Connection:check.html.twig', [
                    'form' => $emailFolderForm->createView(),
                ]);
            } catch (\Exception $e) {
                $this->get('logger')
                    ->critical('Unable to connect to IMAP server: ' . $e->getMessage(), ['exception' => $e]);
            }
        }

        return new Response('', $responseCode);
    }

    /**
     * @param Folder[] $srcFolders
     *
     * @return EmailFolder[]
     */
    protected function processFolders(array $srcFolders)
    {
        $folders = [];
        foreach ($srcFolders as $srcFolder) {
            $folder = null;
            $folderFullName = $srcFolder->getGlobalName();
            $uidValidity = $this->getUidValidity($srcFolder);

            if ($uidValidity !== null) {
                $folder = $this->createFolder($srcFolder, $folderFullName);
                $folders[] = $folder;
            }

            $childSrcFolders = [];
            foreach ($srcFolder as $childSrcFolder) {
                $childSrcFolders[] = $childSrcFolder;
            }

            $childFolders = $this->processFolders($childSrcFolders);
            if (isset($folder)) {
                foreach ($childFolders as $childFolder) {
                    $folder->addSubFolder($childFolder);
                }
            } else {
                $folders = array_merge($folders, $childFolders);
            }
        }

        return $folders;
    }

    /**
     * Performs synchronization of folders
     *
     * @return EmailFolder[] The list of folders for which emails need to be synchronized
     */
    protected function syncFolders()
    {
        //$existingImapFolders = $this->getExistingImapFolders($origin); // todo implement
        $srcFolders = $this->manager->getFolders(null, false);

        return $this->processFolders($srcFolders);
        foreach ($srcFolders as $srcFolder) {
            $folder = null;
            $folderFullName = $srcFolder->getGlobalName();
            $uidValidity = $this->getUidValidity($srcFolder);

            // check if the current folder already exist and has no changes,
            // if so, remove it from the list of existing folders
/*            $imapFolder = null;
            foreach ($existingImapFolders as $key => $existingImapFolder) {
                if ($existingImapFolder->getUidValidity() === $uidValidity
                    && $existingImapFolder->getFolder()->getFullName() === $folderFullName
                ) {
                    $imapFolder = $existingImapFolder;
                    unset($existingImapFolders[$key]);
                    break;
                }
            }*/

            // check if new folder need to be created
            //if (!$imapFolder) { // todo implement
            if ($uidValidity !== null) {
                $folder = new EmailFolder();
                $folder
                    ->setFullName($folderFullName)
                    ->setName($srcFolder->getLocalName())
                    ->setType($srcFolder->guessFolderType());
                //$origin->addFolder($folder);

                $imapFolder = new ImapEmailFolder();
                $imapFolder->setFolder($folder);
                $imapFolder->setUidValidity($uidValidity);

                $folders[] = $folder;
            }
            //}

            // save folder to the list of folders to be synchronized
            //$folders[] = $imapFolder;
        }

        // mark the rest of existing folders as outdated
        // todo implement
/*        foreach ($existingImapFolders as $imapFolder) {
            $this->logger->notice(
                sprintf('Mark "%s" folder as outdated.', $imapFolder->getFolder()->getFullName())
            );
            $imapFolder->getFolder()->setOutdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
            $this->em->persist($imapFolder->getFolder());
        }*/

        // todo clear folders afterwards

        return $folders;
    }

    protected function createFolder(Folder $srcFolder, $fullName)
    {
        $folder = new EmailFolder();
        $folder
            ->setFullName($fullName)
            ->setName($srcFolder->getLocalName())
            ->setType($srcFolder->guessFolderType());

        return $folder;
    }

    /**
     * Gets UIDVALIDITY of the given folder
     *
     * @param Folder $folder
     *
     * @return int|null
     */
    protected function getUidValidity(Folder $folder)
    {
        try {
            $this->manager->selectFolder($folder->getGlobalName());

            return $this->manager->getUidValidity();
        } catch (\Exception $e) {
            return null;
        }
    }
}
