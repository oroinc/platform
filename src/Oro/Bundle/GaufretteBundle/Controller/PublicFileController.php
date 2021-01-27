<?php

namespace Oro\Bundle\GaufretteBundle\Controller;

use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller to get files that are stored in public Gaufrette filesystems.
 */
class PublicFileController extends AbstractController
{
    /** @var iterable|FileManager[] */
    private $publicFileManagers;

    /**
     * @param iterable $publicFileManagers
     */
    public function __construct(iterable $publicFileManagers)
    {
        $this->publicFileManagers = $publicFileManagers;
    }

    /**
     * @param string $subDirectory
     * @param string $fileName
     *
     * @return Response
     */
    public function getPublicFileAction(string $subDirectory, string $fileName): Response
    {
        $fileManager = $this->getPublicFileManager($subDirectory);
        if (null === $fileManager || !$fileManager->hasFile($fileName)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($fileManager->getFilePath($fileName));
        $response->headers->set('Cache-Control', 'public');
        $response->headers->set(
            'Content-Type',
            $fileManager->getFileMimeType($fileName) ?? 'application/octet-stream'
        );

        return $response;
    }

    /**
     * @param string $subDirectory
     *
     * @return FileManager|null
     */
    private function getPublicFileManager(string $subDirectory): ?FileManager
    {
        foreach ($this->publicFileManagers as $fileManager) {
            if ($fileManager->getSubDirectory() === $subDirectory) {
                return $fileManager;
            }
        }

        return null;
    }
}
