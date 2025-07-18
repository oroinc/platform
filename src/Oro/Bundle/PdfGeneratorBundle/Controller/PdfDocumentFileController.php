<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Operator\PdfDocumentOperatorRegistry;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentGenerationMode;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Main entry point for PDF document file downloads.
 * Initiates the download of a PDF document file.
 * Resolves the deferred PDF document file - to generate it if it is not already generated.
 */
final class PdfDocumentFileController extends AbstractController
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            AuthorizationCheckerInterface::class,
            PdfDocumentOperatorRegistry::class,
            ManagerRegistry::class,
        ];
    }

    public function __invoke(PdfDocument $pdfDocument, string $fileAction): Response|array
    {
        /** @var AuthorizationCheckerInterface $authorizationChecker */
        $authorizationChecker = $this->container->get(AuthorizationCheckerInterface::class);
        if (!$authorizationChecker->isGranted(BasicPermission::VIEW, $pdfDocument)) {
            throw $this->createAccessDeniedException();
        }

        $this->resolvePdfDocument($pdfDocument);

        $pdfDocumentFile = $pdfDocument->getPdfDocumentFile();
        if (!$pdfDocumentFile) {
            throw $this->createNotFoundException();
        }

        return $this->forward(
            'oro_attachment.controller.file::getFileAction',
            [
                'id' => $pdfDocumentFile->getId(),
                'filename' => $pdfDocumentFile->getFilename(),
                'action' => $fileAction,
            ]
        );
    }

    private function resolvePdfDocument(PdfDocument $pdfDocument): void
    {
        /** @var PdfDocumentOperatorRegistry $pdfDocumentOperatorRegistry */
        $pdfDocumentOperatorRegistry = $this->container->get(PdfDocumentOperatorRegistry::class);
        $sourceEntityClass = $pdfDocument->getSourceEntityClass();
        $pdfGenerationMode = PdfDocumentGenerationMode::INSTANT;

        $pdfDocumentOperator = $pdfDocumentOperatorRegistry->getOperator($sourceEntityClass, $pdfGenerationMode);
        if (!$pdfDocumentOperator->isResolvedPdfDocument($pdfDocument)) {
            $pdfDocumentOperator->resolvePdfDocument($pdfDocument);

            /** @var ManagerRegistry $doctrine */
            $doctrine = $this->container->get(ManagerRegistry::class);
            $entityManager = $doctrine->getManagerForClass(PdfDocument::class);
            $entityManager->flush();
        }
    }
}
