<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * @dbIsolationPerTest
 */
class UserProfileTest extends RestJsonApiTestCase
{
    private function getCurrentUser(): User
    {
        return self::getContainer()->get('security.token_storage')->getToken()->getUser();
    }

    private function getExpectedContent(array $expectedContent): array
    {
        $content = Yaml::dump($expectedContent);
        $content = str_replace('{baseUrl}', $this->getApiBaseUrl(), $content);

        return self::processTemplateData(Yaml::parse($content));
    }

    public function testGet()
    {
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_user_profile')
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $user = $this->getCurrentUser();
        $this->assertResponseContains([
            'data' => [
                'type'          => 'userprofile',
                'id'            => (string)$user->getId(),
                'attributes'    => [
                    'username' => self::USER_NAME,
                ],
                'relationships' => [
                    'owner' => [
                        'data' => ['type' => 'businessunits', 'id' => (string)$user->getOwner()->getId()]
                    ]
                ]
            ]
        ], $response);
    }

    public function testGetWithHateoasLinks()
    {
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_user_profile'),
            [],
            ['HTTP_HATEOAS' => true]
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $user = $this->getCurrentUser();
        $this->assertResponseContains($this->getExpectedContent([
            'data' => [
                'type'          => 'userprofile',
                'id'            => (string)$user->getId(),
                'links'         => [
                    'self'    => '{baseUrl}/userprofile',
                    'related' => '{baseUrl}/users/' . $user->getId()
                ],
                'relationships' => [
                    'owner' => [
                        'links' => [
                            'self'    => '{baseUrl}/users/' . $user->getId() . '/relationships/owner',
                            'related' => '{baseUrl}/users/' . $user->getId() . '/owner'
                        ]
                    ]
                ]
            ]
        ]), $response);
    }

    public function testOptions()
    {
        $response = $this->options('oro_rest_api_user_profile');
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    /**
     * @dataProvider getNotAllowedMethods
     */
    public function testNotAllowedMethods($method)
    {
        $response = $this->request(
            $method,
            $this->getUrl('oro_rest_api_user_profile')
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    /**
     * @return array
     */
    public function getNotAllowedMethods()
    {
        return [
            ['HEAD'],
            ['POST'],
            ['PATCH'],
            ['DELETE']
        ];
    }
}
