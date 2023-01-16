<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class UserProfileTest extends RestJsonApiTestCase
{
    private function getCurrentUser(): User
    {
        return self::getContainer()->get('security.token_storage')->getToken()->getUser();
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
        $this->assertResponseContains($this->getExpectedContentWithPaginationLinks([
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
    public function testNotAllowedMethods(string $method)
    {
        $response = $this->request(
            $method,
            $this->getUrl('oro_rest_api_user_profile')
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function getNotAllowedMethods(): array
    {
        return [
            ['HEAD'],
            ['POST'],
            ['PATCH'],
            ['DELETE']
        ];
    }
}
