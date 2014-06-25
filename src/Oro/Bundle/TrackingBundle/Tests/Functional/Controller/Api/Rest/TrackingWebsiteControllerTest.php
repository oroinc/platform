<?php

namespace Oro\Bundle\TrackingBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class TrackingWebsiteControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(
            [
                'Oro\Bundle\TrackingBundle\Tests\Functional\Controller\Api\Rest\DataFixtures\LoadTrackingWebsiteData',
            ]
        );
    }

    public function testDelete()
    {
        $class = $this->client
            ->getContainer()
            ->getParameter('oro_tracking.tracking_website.class');

        $website = $this->client
            ->getContainer()
            ->get('doctrine')
            ->getManagerForClass($class)
            ->getRepository($class)
            ->findOneBy(['identifier' => 'delete']);

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_tracking_website', ['id' => $website->getId()]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 204);
    }
}
