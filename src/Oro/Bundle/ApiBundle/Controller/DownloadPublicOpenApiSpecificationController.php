<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\ApiBundle\Request\Rest\CorsHeaders;
use Oro\Bundle\ApiBundle\Util\OpenApiSpecificationArchive;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The controller for download public OpenAPI specification.
 */
class DownloadPublicOpenApiSpecificationController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly OpenApiSpecificationArchive $openApiArchive,
        private readonly int $preflightMaxAge,
        private readonly array $allowedOrigins,
        private readonly array $allowedHeaders
    ) {
    }

    #[Route(
        path: '/public-openapi-specification/{organizationId}/{slug}',
        name: 'oro_public_openapi_specification_download',
        requirements: ['organizationId' => '.+', 'slug' => '.+'],
        methods: [Request::METHOD_GET]
    )]
    public function downloadPublicAction(Request $request): Response
    {
        $organizationId = filter_var($request->attributes->get('organizationId'), FILTER_VALIDATE_INT);
        if (false === $organizationId) {
            throw $this->createNotFoundHttpException($request, 'The OpenAPI specification does not exists.');
        }
        $entity = $this->doctrine->getRepository(OpenApiSpecification::class)
            ->findOneBy(['publicSlug' => $request->attributes->get('slug'), 'organization' => $organizationId]);
        if (null === $entity) {
            throw $this->createNotFoundHttpException($request, 'The OpenAPI specification does not exists.');
        }
        if (!$entity->isPublished()) {
            throw $this->createNotFoundHttpException($request, 'The OpenAPI specification is not published yet.');
        }
        if ($entity->getStatus() === OpenApiSpecification::STATUS_CREATING) {
            throw $this->createNotFoundHttpException($request, 'The OpenAPI specification is not created yet.');
        }
        if (null === $entity->getSpecificationCreatedAt()) {
            throw $this->createNotFoundHttpException($request, 'The creation of the OpenAPI specification failed.');
        }

        try {
            $specification = $this->openApiArchive->decompress($entity->getSpecification());
        } catch (\Throwable $e) {
            throw $this->createNotFoundHttpException($request, $e->getMessage());
        }

        $response = new Response($specification);
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Content-Type', $this->getSpecificationFileMediaType($entity->getFormat()));
        $response->headers->set('Content-Length', \strlen($specification));
        if ($this->isCorsRequest($request)) {
            $origin = $request->headers->get(CorsHeaders::ORIGIN);
            if ($this->isAllowedOrigin($origin)) {
                $response->headers->set(CorsHeaders::ACCESS_CONTROL_ALLOW_ORIGIN, $origin);
            }
        }

        return $response;
    }

    #[Route(
        path: '/public-openapi-specification/{organizationId}/{slug}',
        name: 'oro_public_openapi_specification_download_options',
        requirements: ['organizationId' => '.+', 'slug' => '.+'],
        defaults: ['organizationId' => 0, 'slug' => '_'],
        methods: [Request::METHOD_OPTIONS]
    )]
    public function optionsAction(Request $request): Response
    {
        $response = new Response();
        $response->headers->set('Allow', implode(', ', [Request::METHOD_OPTIONS, Request::METHOD_GET]));
        if ($this->isCorsRequest($request)) {
            $origin = $request->headers->get(CorsHeaders::ORIGIN);
            if ($this->isAllowedOrigin($origin)) {
                $response->headers->set(CorsHeaders::ACCESS_CONTROL_ALLOW_ORIGIN, $origin);
            }
            $requestMethod = $request->headers->get(CorsHeaders::ACCESS_CONTROL_REQUEST_METHOD);
            if ($requestMethod) {
                $response->headers->set(CorsHeaders::ACCESS_CONTROL_ALLOW_METHODS, $response->headers->get('Allow'));
                $response->headers->remove('Allow');
                $response->headers->set(CorsHeaders::ACCESS_CONTROL_ALLOW_HEADERS, implode(',', $this->allowedHeaders));
                if ($this->preflightMaxAge > 0) {
                    $response->headers->set(CorsHeaders::ACCESS_CONTROL_MAX_AGE, $this->preflightMaxAge);
                    // although OPTIONS requests are not cacheable, add "Cache-Control" header
                    // indicates that a caching is enabled to prevent making CORS preflight requests not cacheable
                    $response->headers->set('Cache-Control', \sprintf('max-age=%d, public', $this->preflightMaxAge));
                    // the response depends on the Origin header value and should therefore not be served
                    // from cache for any other origin
                    $response->headers->set('Vary', CorsHeaders::ORIGIN);
                }
            }
        }

        return $response;
    }

    private function createNotFoundHttpException(Request $request, string $message): NotFoundHttpException
    {
        $exception = new NotFoundHttpException($message);
        if ($this->isCorsRequest($request)) {
            $origin = $request->headers->get(CorsHeaders::ORIGIN);
            if ($this->isAllowedOrigin($origin)) {
                $exception->setHeaders([CorsHeaders::ACCESS_CONTROL_ALLOW_ORIGIN => $origin]);
            }
        }

        return $exception;
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

    private function isCorsRequest(Request $request): bool
    {
        return
            $request->headers->has(CorsHeaders::ORIGIN)
            && $request->headers->get(CorsHeaders::ORIGIN) !== $request->getSchemeAndHttpHost();
    }

    private function isAllowedOrigin(string $origin): bool
    {
        foreach ($this->allowedOrigins as $allowedOrigin) {
            if ('*' === $allowedOrigin || $origin === $allowedOrigin) {
                return true;
            }
        }

        return false;
    }
}
