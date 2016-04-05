<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class IntegrationControllerTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->entityManager = $this->client->getContainer()->get('doctrine')
            ->getManagerForClass('OroIntegrationBundle:Channel');
    }

    public function testActivateDeactivate()
    {
        $channel = $this->createNewEnabledChannel();

        $this->client->request('GET', $this->getUrl('oro_api_deactivate_integration', ['id' => $channel->getId()]));

        $this->assertResult($this->getJsonResponseContent($this->client->getResponse(), 200));
        
        $channel = $this->refreshEntity($channel);
        $this->assertFalse($channel->isEnabled());

        // activate process
        $this->client->request('GET', $this->getUrl('oro_api_activate_integration', ['id' => $channel->getId()]));

        $this->assertResult($this->getJsonResponseContent($this->client->getResponse(), 200));

        $channel = $this->refreshEntity($channel);
        $this->assertTrue($channel->isEnabled());
    }

    /**
     * @param array $result
     */
    protected function assertResult($result)
    {
        $this->assertArrayHasKey('successful', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['successful']);
        $this->assertNotEmpty($result['message']);
    }

    protected function refreshEntity(Channel $channel)
    {
        return $this->entityManager->find('OroIntegrationBundle:Channel', $channel->getId());
    }

    /**
     * @return Channel
     */
    protected function createNewEnabledChannel()
    {
        $channel = new Channel();
        $channel->setName('aName');
        $channel->setType('aType');
        $channel->setEnabled(true);

        $this->entityManager->persist($channel);
        $this->entityManager->flush($channel);

        return $channel;
    }
}
