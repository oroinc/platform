<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractChannelDataDeleteTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var string
     */
    protected $entityClassName;

    public function setUp()
    {
        $this->initClient(
            array(),
            array_merge($this->generateBasicAuthHeader(), array('HTTP_X-CSRF-Header' => 1))
        );
        $this->container = $this->client->getKernel()->getContainer();
        $this->em = $this->container->get('doctrine.orm.entity_manager');
    }

    public function testDeleteChannel()
    {
        $this->generateTestData();
        $channelId = $this->channel->getId();
        $this->container->get('oro_integration.channel_delete_manager')->deleteChannel($this->channel);

        $resultChannel = $this->em->getRepository('OroIntegrationBundle:Channel')->find($channelId);
        $resultForm = $this->em->getRepository($this->entityClassName)
            ->findOneBy(['channel' => $channelId]);

        $this->assertNull($resultChannel);
        $this->assertNull($resultForm);
    }

    /**
     * Create related entity
     *
     * @param Channel $channel
     */
    abstract protected function createRelatedEntity(Channel $channel);

    /**
     * Generate test channel with related entity
     */
    protected function generateTestData()
    {
        $this->channel = new Channel();
        $this->channel->setType('simple')
            ->setName('test');
        $this->em->persist($this->channel);
        $this->createRelatedEntity($this->channel);
        $this->em->flush();
    }
}
