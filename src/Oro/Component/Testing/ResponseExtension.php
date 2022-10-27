<?php

namespace Oro\Component\Testing;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides assertion for the Response object.
 */
trait ResponseExtension
{
    public function assertResponseStatus(Response $response, int $expectedStatus): void
    {
        $this->assertInstanceOfResponse($response);
        self::assertEquals($expectedStatus, $response->getStatusCode(), $this->getAssertMessage($response));
    }

    public function assertLastResponseStatus(int $expectedStatus): void
    {
        $this->assertResponseStatus($this->getClientInstance()->getResponse(), $expectedStatus);
    }

    public function assertResponseContentTypeHtml(Response $response): void
    {
        $this->assertInstanceOfResponse($response);

        self::assertTrue($response->headers->has('Content-Type'));
        static::assertStringContainsString(
            'text/html',
            $response->headers->get('Content-Type'),
            $this->getAssertMessage($response)
        );
    }

    public function assertLastResponseContentTypeHtml(): void
    {
        $this->assertResponseContentTypeHtml($this->getClientInstance()->getResponse());
    }

    public function assertResponseContentTypeJson(Response $response): void
    {
        $this->assertInstanceOfResponse($response);

        self::assertTrue($response->headers->has('Content-Type'));
        self::assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    public function assertLastResponseContentTypeJson(): void
    {
        $this->assertResponseContentTypeJson($this->getClientInstance()->getResponse());
    }

    /**
     * @param Response $response
     *
     * @return array
     */
    public function getResponseJsonContent(Response $response)
    {
        $this->assertInstanceOfResponse($response);

        $content = json_decode($response->getContent(), true);

        //guard
        self::assertNotNull(
            $content,
            sprintf("The response content is not valid json.\n\n%s", $response->getContent())
        );

        return $content;
    }
    /**
     * @return array
     */
    public function getLastResponseJsonContent()
    {
        return $this->getResponseJsonContent($this->getClientInstance()->getResponse());
    }

    /**
     * @param mixed $actual
     */
    public function assertInstanceOfResponse($actual): void
    {
        self::assertInstanceOf(Response::class, $actual);
    }

    private function getAssertMessage(Response $response): string
    {
        $responseStatusCode = $response->getStatusCode();
        if (500 >= $responseStatusCode && $responseStatusCode < 600) {
            $crawler = new Crawler();
            $crawler->addHtmlContent($response->getContent());
            if ($crawler->filter('.text-exception h1')->count() > 0) {
                $exceptionMessage = trim($crawler->filter('.text-exception h1')->text());
                $trace = '';
                if ($crawler->filter('#traces-0 li')->count() > 0) {
                    list($trace) = explode("\n", trim($crawler->filter('#traces-0 li')->text()));
                }
                return 'Internal Server Error: '.$exceptionMessage.' '.$trace;
            }
        }
        return $response->getContent();
    }

    /**
     * @return KernelBrowser
     */
    abstract protected static function getClientInstance();
}
