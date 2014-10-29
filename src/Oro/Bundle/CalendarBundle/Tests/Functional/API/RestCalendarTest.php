<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use FOS\Rest\Util\Codes;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RestCalendarTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
    }

    /**
     * Test get default calendar of user
     */
    public function testGetDefaultCalendarAction()
    {
        $request = array(
            "user"          => 1,
            "organization"  => 1,
        );

        $this->client->request('GET', $this->getUrl('oro_api_get_calendar_default', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), Codes::HTTP_OK);

        $this->assertNotEmpty($result);
    }
}
