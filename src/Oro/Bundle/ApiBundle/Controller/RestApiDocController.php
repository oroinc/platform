<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Nelmio\ApiDocBundle\Formatter\FormatterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API Sandbox controller.
 */
class RestApiDocController extends Controller
{
    /**
     * @param string $view
     *
     * @return Response
     */
    public function indexAction($view = ApiDoc::DEFAULT_VIEW)
    {
        return $this->getHtmlResponse(
            $this->getFormatter()->format($this->getExtractor()->all($view))
        );
    }

    /**
     * @param string $view
     * @param string $resource
     *
     * @return Response
     */
    public function resourceAction($view, $resource)
    {
        $extractedResource = $this->getApiResource($view, $resource);
        if (null === $extractedResource) {
            return $this->getResourceNotFoundResponse();
        }

        return $this->getHtmlResponse(
            $this->getFormatter()->formatOne($extractedResource)
        );
    }

    /**
     * @param string $view
     * @param string $resource
     *
     * @return ApiDoc|null
     */
    private function getApiResource($view, $resource): ?ApiDoc
    {
        $extractedResource = null;
        $extractedDoc = $this->getExtractor()->all($view);
        foreach ($extractedDoc as $item) {
            $annotation = $item['annotation'];
            if ($this->getResourceId($annotation) === $resource) {
                $extractedResource = $annotation;
                break;
            }
        }

        return $extractedResource;
    }

    /**
     * @param ApiDoc $annotation
     *
     * @return string
     */
    private function getResourceId(ApiDoc $annotation): string
    {
        return
            strtolower($annotation->getMethod())
            . '-'
            . str_replace('/', '-', $annotation->getRoute()->getPath());
    }

    /**
     * @param string $content
     *
     * @return Response
     */
    private function getHtmlResponse(string $content): Response
    {
        return new Response(
            $content,
            Response::HTTP_OK,
            ['Content-Type' => 'text/html']
        );
    }

    /**
     * @return Response
     */
    private function getResourceNotFoundResponse(): Response
    {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    /**
     * @return ApiDocExtractor
     */
    private function getExtractor()
    {
        return $this->get('nelmio_api_doc.extractor.api_doc_extractor');
    }

    /**
     * @return FormatterInterface
     */
    private function getFormatter()
    {
        return $this->get('nelmio_api_doc.formatter.html_formatter');
    }
}
