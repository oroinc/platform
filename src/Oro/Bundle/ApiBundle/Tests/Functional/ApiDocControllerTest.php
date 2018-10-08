<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class ApiDocControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    /**
     * @param string|null $view
     *
     * @return Response
     */
    private function sendApiDocRequest(string $view = null): Response
    {
        $parameters = [];
        if (null !== $view) {
            $parameters['view'] = $view;
        }
        $this->client->request(
            'GET',
            $this->getUrl('nelmio_api_doc_index', $parameters)
        );

        return $this->client->getResponse();
    }

    public function testUnknownView()
    {
        $response = $this->sendApiDocRequest('unknown');
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testDefaultView()
    {
        $response = $this->sendApiDocRequest();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
    }

    public function testRestJsonApiView()
    {
        $response = $this->sendApiDocRequest('rest_json_api');
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
    }

    public function testRestPlainView()
    {
        $response = $this->sendApiDocRequest('rest_plain');
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
    }
}
