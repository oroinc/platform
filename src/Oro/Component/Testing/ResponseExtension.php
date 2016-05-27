<?php
namespace Oro\Component\Testing;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

trait ResponseExtension
{
    /**
     * @param Response $response
     * @param int $expectedStatus
     */
    public function assertResponseStatus($response, $expectedStatus)
    {
        $this->assertInstanceOfResponse($response);
        $this->assertEquals($expectedStatus, $response->getStatusCode(), $this->getAssertMessage($response));
    }
    /**
     * @param int $expectedStatus
     */
    public function assertLastResponseStatus($expectedStatus)
    {
        $this->assertResponseStatus($this->getClient()->getResponse(), $expectedStatus);
    }

    /**
     * @param Response $response
     */
    public function assertResponseContentTypeHtml($response)
    {
        $this->assertInstanceOfResponse($response);

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertContains(
            'text/html',
            $response->headers->get('Content-Type'),
            $this->getAssertMessage($response)
        );
    }

    public function assertLastResponseContentTypeHtml()
    {
        $this->assertResponseContentTypeHtml($this->getClient()->getResponse());
    }

    /**
     * @param Response $response
     */
    public function assertResponseContentTypeJson($response)
    {
        $this->assertInstanceOfResponse($response);

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    public function assertLastResponseContentTypeJson()
    {
        $this->assertResponseContentTypeJson($this->getClient()->getResponse());
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return array
     */
    public function getResponseJsonContent($response)
    {
        $this->assertInstanceOfResponse($response);

        $content = json_decode($response->getContent(), $assoc = true);

        //guard
        $this->assertNotNull(
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
        return $this->getResponseJsonContent($this->getClient()->getResponse());
    }

    /**
     * @param mixed $actual
     */
    public function assertInstanceOfResponse($actual)
    {
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $actual);
    }

    /**
     * @param Response $response
     *
     * @return string
     */
    private function getAssertMessage(Response $response)
    {
        if (500 >= $response->getStatusCode() && $response->getStatusCode() < 600) {
            $crawler = new Crawler();
            $crawler->addHtmlContent($response->getContent());
            if ($crawler->filter('.text-exception h1')->count() > 0) {
                $exceptionMessage = trim($crawler->filter('.text-exception h1')->text());
                $trace = '';
                if ($crawler->filter('#traces-0 li')->count() > 0) {
                    list($trace) = explode("\n", trim($crawler->filter('#traces-0 li')->text()));
                }
                return $message = 'Internal Server Error: '.$exceptionMessage.' '.$trace;
            }
        }
        return $response->getContent();
    }

    /**
     * @return Client
     */
    abstract protected function getClient();
}
