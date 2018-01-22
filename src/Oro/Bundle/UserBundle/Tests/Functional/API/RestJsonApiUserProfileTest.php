<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\UserBundle\Api\Model\UserProfile;

/**
 * @dbIsolationPerTest
 */
class RestJsonApiUserProfileTest extends RestJsonApiTestCase
{
    public function testGet()
    {
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_get_user_profile')
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

    /**
     * @dataProvider getNotAllowedMethods
     */
    public function testNotAllowedMethods($method)
    {
        $response = $this->request(
            $method,
            $this->getUrl('oro_rest_api_get_user_profile')
        );

        // @todo: should be fixed in BAP-16413
        self::assertResponseStatusCodeEquals($response, [404, 405]);
        //correct assert should be:
        //self::assertResponseStatusCodeEquals($response, 405);
        //self::assertEquals('GET', $response->headers->get('Allow'));
    }

    /**
     * @return array
     */
    public function getNotAllowedMethods()
    {
        return [
            ['POST'],
            ['PATCH'],
            ['DELETE'],
        ];
    }
}
