<?php

namespace OroCRM\Bundle\IntegrationBundle\Tests\Functional\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;

class ChannelDeleteManagerTest extends WebTestCase
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

    public function setUp()
    {
        $client = static::createClient(
            array(),
            array_merge(ToolsAPI::generateBasicHeader(), array('HTTP_X-CSRF-Header' => 1))
        );
        $this->container = $client->getKernel()->getContainer();
        $this->em = $this->container->get('doctrine.orm.entity_manager');
    }

    public function testDeleteChannel()
    {
        $this->generateTestData();
        $channelId = $this->channel->getId();
        $this->container->get('oro_integration.channel_delete_manager')->deleteChannel($this->channel);

        $resultChannel = $this->em->getRepository('OroIntegrationBundle:Channel')->find($channelId);
        $resultForm = $this->em->getRepository('OroEmbeddedFormBundle:EmbeddedForm')
            ->findOneBy(['channel' => $channelId]);

        $this->assertNull($resultChannel);
        $this->assertNull($resultForm);
    }

    /**
     * Generate test channel with assigned embedded form
     */
    protected function generateTestData()
    {
        $this->channel = new Channel();
        $this->channel->setType('simple')
            ->setName('test');
        $embeddedForm = new EmbeddedForm();
        $embeddedForm->setTitle('test');
        $embeddedForm->setCss('');
        $embeddedForm->setFormType('test');
        $embeddedForm->setSuccessMessage('test');
        $embeddedForm->setChannel($this->channel);
        $this->em->persist($this->channel);
        $this->em->persist($embeddedForm);
        $this->em->flush();
    }
}
