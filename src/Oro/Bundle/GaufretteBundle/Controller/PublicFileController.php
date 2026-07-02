<?php

declare(strict_types=1);

namespace Oro\Bundle\GaufretteBundle\Controller;

use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypes;

/**
 * The controller to get files that are stored in public Gaufrette filesystems.
 */
class PublicFileController extends AbstractController
{
    private const DEFAULT_MIME_TYPE = 'application/octet-stream';

    /** @var iterable|FileManager[] */
    private $publicFileManagers;

    public function __construct(iterable $publicFileManagers)
    {
        $this->publicFileManagers = $publicFileManagers;
    }

    public function getPublicFileAction(string $subDirectory, string $fileName): Response
    {
        $fileManager = $this->getPublicFileManager($subDirectory);
        if (null === $fileManager || !$fileManager->hasFile($fileName)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($fileManager->getFilePath($fileName));
        $response->headers->set('Cache-Control', 'public');
        $response->headers->set('Content-Type', $this->resolveMimeType($fileManager, $fileName));

        return $response;
    }

    private function getPublicFileManager(string $subDirectory): ?FileManager
    {
        foreach ($this->publicFileManagers as $fileManager) {
            if ($fileManager->getSubDirectory() === $subDirectory) {
                return $fileManager;
            }
        }

        return null;
    }

    private function resolveMimeType(FileManager $fileManager, string $fileName): string
    {
        $mimeType = $fileManager->getFileMimeType($fileName);
        if (null === $mimeType) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $mimeTypes = (new MimeTypes())
                ->getMimeTypes($extension);

            $mimeType = $mimeTypes[0] ?? null;
        }

        return $mimeType ?: self::DEFAULT_MIME_TYPE;
    }
}
