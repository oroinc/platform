<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\UserBundle\Api\Model\UserProfile;

/**
 * @dbIsolationPerTest
 */
class UserProfileTest extends RestJsonApiTestCase
{
    public function testGet()
    {
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_user_profile')
        );

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            $this->getEntityType(UserProfile::class),
            $result['data']['type']
        );
        self::assertEquals(
            (string)$this->getContainer()->get('security.token_storage')->getToken()->getUser()->getId(),
            $result['data']['id']
        );
        self::assertEquals(
            self::USER_NAME,
            $result['data']['attributes']['username']
        );
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
