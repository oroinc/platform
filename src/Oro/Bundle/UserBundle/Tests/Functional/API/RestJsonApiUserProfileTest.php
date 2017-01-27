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
}
