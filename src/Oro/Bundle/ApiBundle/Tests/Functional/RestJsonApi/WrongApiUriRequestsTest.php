<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WrongApiUriRequestsTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/custom_entities.yml'
        ]);
    }

    private function getWebBackendPrefix(): string
    {
        return self::getContainer()->hasParameter('web_backend_prefix')
            ? self::getContainer()->getParameter('web_backend_prefix')
            : '';
    }

    private function getWebBackendPrefixPath(): string
    {
        $prefix = $this->getWebBackendPrefix();

        return $prefix
            ? '/..' . $prefix
            : '';
    }

    public function testTryToGetAnotherApiResourceWithFullReplaceOfBaseUrl(): void
    {
        $baseUrl = $this->getUrl($this->getListRouteName(), ['entity' => 'testapientity1']);
        $additionalUrl = $this->getUrl($this->getItemRouteName(), ['entity' => 'users', 'id' => 1]);
        $slashesCount = substr_count($baseUrl, '/') - 1;

        $response = $this->request(
            'GET',
            $baseUrl . str_repeat('/..', $slashesCount) . $additionalUrl
        );

        $this->assertResponseContainsValidationError(
            [
                'status' => '404',
                'title'  => 'not found http exception',
                'detail' => sprintf(
                    'No route found for "GET http://localhost%1$s/api/testapientity1/..%2$s/api/users/1".',
                    $this->getWebBackendPrefix(),
                    $this->getWebBackendPrefixPath()
                )
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGetAnotherApiResource(): void
    {
        $baseUrl = $this->getUrl($this->getListRouteName(), ['entity' => 'testapientity1']);

        $response = $this->request(
            'GET',
            $baseUrl . '/../users/1'
        );

        $this->assertResponseContainsValidationError(
            [
                'status' => '404',
                'title'  => 'not found http exception',
                'detail' => sprintf(
                    'No route found for "GET http://localhost%1$s/api/testapientity1/../users/1"'
                    . ' (from "http://localhost%1$s/api/testapientity1/..%2$s/api/users/1").',
                    $this->getWebBackendPrefix(),
                    $this->getWebBackendPrefixPath()
                )
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGetUserViewPageThroughtApiRequest(): void
    {
        $baseUrl = $this->getUrl($this->getListRouteName(), ['entity' => 'testapientity1']);
        $additionalUrl = self::getContainer()
            ->get('router')
            ->generate('oro_user_view', ['id' => 1], UrlGeneratorInterface::ABSOLUTE_PATH);
        $slashesCount = substr_count($baseUrl, '/') - 1;

        $response = $this->request(
            'GET',
            $baseUrl . str_repeat('/..', $slashesCount) . $additionalUrl
        );

        $this->assertResponseContainsValidationError(
            [
                'status' => '404',
                'title'  => 'not found http exception',
                'detail' => sprintf(
                    'No route found for "GET http://localhost%1$s/api/testapientity1/..%2$s/user/view/1"'
                    . ' (from "http://localhost%1$s/api/testapientity1/../users/1").',
                    $this->getWebBackendPrefix(),
                    $this->getWebBackendPrefixPath()
                )
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }
}
