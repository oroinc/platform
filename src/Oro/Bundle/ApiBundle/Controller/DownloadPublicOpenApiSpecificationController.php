<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\ApiBundle\Util\OpenApiSpecificationArchive;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for download public OpenAPI specification.
 */
class DownloadPublicOpenApiSpecificationController
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private OpenApiSpecificationArchive $openApiArchive
    ) {
    }

    #[Route(
        path: '/public-openapi-specification/{organizationId}/{slug}',
        name: 'oro_public_openapi_specification_download',
        requirements: ['organizationId' => '\d+', 'slug' => '[a-zA-Z0-9_\-]+']
    )]
    public function downloadPublicAction(int $organizationId, string $slug): Response
    {
        $entity = $this->doctrine->getRepository(OpenApiSpecification::class)
            ->findOneBy(['publicSlug' => $slug, 'organization' => $organizationId]);
        if (null === $entity) {
            throw new NotFoundHttpException('The OpenAPI specification does not exists.');
        }
        if (!$entity->isPublished()) {
            throw new NotFoundHttpException('The OpenAPI specification is not published yet.');
        }
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
        $response->headers->set('Content-Type', $this->getSpecificationFileMediaType($entity->getFormat()));
        $response->headers->set('Content-Length', \strlen($specification));

        return $response;
    }

    private function getSpecificationFileMediaType(string $format): string
    {
        switch ($format) {
            case 'json':
            case 'json-pretty':
                return 'application/json';
            case 'yaml':
                return 'application/yaml';
            default:
                return 'text/plain';
        }
    }
}
