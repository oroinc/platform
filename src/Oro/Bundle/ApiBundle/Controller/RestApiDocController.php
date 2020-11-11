<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Nelmio\ApiDocBundle\Formatter\FormatterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * REST API Sandbox controller.
 */
class RestApiDocController
{
    /** @var ApiDocExtractor */
    private $extractor;

    /** @var FormatterInterface */
    private $formatter;

    /** @var SessionInterface */
    private $session;

    /**
     * @param ApiDocExtractor    $extractor
     * @param FormatterInterface $formatter
     * @param SessionInterface   $session
     */
    public function __construct(ApiDocExtractor $extractor, FormatterInterface $formatter, SessionInterface $session)
    {
        $this->extractor = $extractor;
        $this->formatter = $formatter;
        $this->session = $session;
    }

    /**
     * @param string $view
     *
     * @return Response
     */
    public function indexAction(string $view = ApiDoc::DEFAULT_VIEW): Response
    {
        $response = $this->getHtmlResponse(
            $this->formatter->format($this->extractor->all($view))
        );
        $this->ensureSessionExists();

        return $response;
    }

    /**
     * @param string $view
     * @param string $resource
     *
     * @return Response
     */
    public function resourceAction(string $view, string $resource): Response
    {
        $apiResource = $this->getApiResource($view, $resource);
        if (null === $apiResource) {
            return $this->getResourceNotFoundResponse();
        }

        $response = $this->getHtmlResponse(
            $this->formatter->formatOne($apiResource)
        );
        $this->ensureSessionExists();

        return $response;
    }

    /**
     * @param string $view
     * @param string $resource
     *
     * @return ApiDoc|null
     */
    private function getApiResource(string $view, string $resource): ?ApiDoc
    {
        $apiResource = null;
        $apiDoc = $this->extractor->all($view);
        foreach ($apiDoc as $item) {
            $annotation = $item['annotation'];
            if ($this->getResourceId($annotation) === $resource) {
                $apiResource = $annotation;
                break;
            }
        }

        return $apiResource;
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

    private function ensureSessionExists(): void
    {
        /**
         * To correct work of "Session" authentication type on API Sandbox, the session must exist
         * and a cookie with the session identifier must be sent to a browser.
         * To achieve this we just add some value to the session if the session does not contain it yet.
         */
        if (!$this->session->get('api_sandbox')) {
            $this->session->set('api_sandbox', true);
        }
    }
}
