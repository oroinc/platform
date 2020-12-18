<?php

namespace Oro\Bundle\GaufretteBundle\Controller;

use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to access file that stores at the public gaufrette filesystem.
 */
class PublicFileController extends AbstractController
{
    /** @var FileManager[] */
    private $publicFileManagers;

    /**
     * @param iterable $publicFileManagers
     */
    public function __construct(iterable $publicFileManagers)
    {
        $this->publicFileManagers = $publicFileManagers;
    }

    /**
     * @param string $filePrefixDir
     * @param string $filePath
     */
    public function getPublicFileAction(string $filePrefixDir, string $filePath)
    {
        foreach ($this->publicFileManagers as $fileManager) {
            if ($fileManager->getPrefixDirectory() === $filePrefixDir) {
                if (!$fileManager->hasFile($filePath)) {
                    throw $this->createNotFoundException();
                }
                $response = new Response();
                $response->headers->set('Cache-Control', 'public');
                $response->headers->set(
                    'Content-Type',
                    $fileManager->mimeType($filePath)?: 'application/force-download'
                );
                $response->setContent($fileManager->getFileContent($filePath));

                return $response;
            }
        }

        throw $this->createNotFoundException();
    }
}
