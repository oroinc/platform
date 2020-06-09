<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class WrongApiUriRequestsTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/custom_entities.yml'
        ]);
    }

    public function testTryToGetAnotherApiResourceWithFullReplaceOfBaseUrl()
    {
        $baseUrl = $this->getUrl($this->getListRouteName(), ['entity' => 'testapientity1']);
        $additionalUrl = $this->getUrl($this->getItemRouteName(), ['entity' => 'users', 'id' => 1]);
        $slashesCount = substr_count($baseUrl, '/') - 1;

        $response = $this->request(
            'GET',
            $baseUrl . str_repeat('/..', $slashesCount) . $additionalUrl
        );

        $this->assertNotFoundResponce($response);
    }

    public function testTryToGetAnotherApiResource()
    {
        $baseUrl = $this->getUrl($this->getListRouteName(), ['entity' => 'testapientity1']);

        $response = $this->request(
            'GET',
            $baseUrl . '/../users/1'
        );

        $this->assertNotFoundResponce($response);
    }

    public function testTryToGetUserViewPageThroughtApiRequest()
    {
        $baseUrl = $this->getUrl($this->getListRouteName(), ['entity' => 'testapientity1']);
        $additionalUrl = self::getContainer()->get('router')->generate('oro_user_view', ['id' => 1], false);
        $slashesCount = substr_count($baseUrl, '/') - 1;

        $response = $this->request(
            'GET',
            $baseUrl . str_repeat('/..', $slashesCount) . $additionalUrl
        );

        $this->assertNotFoundResponce($response);
    }

    private function assertNotFoundResponce(Response $response)
    {
        $this->assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        $this->assertResponseContains(
            [
                'code' => Response::HTTP_NOT_FOUND,
                'message' => 'Not Found'
            ],
            $response
        );
    }
}
