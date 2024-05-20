<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\ApiBundle\Util\OpenApiSpecificationArchive;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for download OpenAPI specification.
 */
#[Route(path: '/openapi-specification')]
class DownloadOpenApiSpecificationController
{
    public function __construct(
        private OpenApiSpecificationArchive $openApiArchive
    ) {
    }

    #[Route(path: '/download/{id}', name: 'oro_openapi_specification_download', requirements: ['id' => '\d+'])]
    #[AclAncestor('oro_openapi_specification_view')]
    public function downloadAction(OpenApiSpecification $entity): Response
    {
        if ($entity->getStatus() === OpenApiSpecification::STATUS_CREATING) {
            throw new NotFoundHttpException('The OpenAPI specification is not created yet.');
        }
        if (null === $entity->getSpecificationCreatedAt()) {
            throw new NotFoundHttpException('The creation of the OpenAPI specification failed.');
        }

        try {
            $specification = $this->openApiArchive->decompress($entity->getSpecification());
        } catch (\Throwable $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        $response = new Response($specification);
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Length', \strlen($specification));
        $response->headers->set(
            'Content-Disposition',
            sprintf('attachment;filename="specification.%s"', $this->getSpecificationFileExt($entity->getFormat()))
        );

        return $response;
    }

    private function getSpecificationFileExt(string $format): string
    {
        switch ($format) {
            case 'json':
            case 'json-pretty':
                return 'json';
            case 'yaml':
                return 'yml';
            default:
                return 'txt';
        }
    }
}
