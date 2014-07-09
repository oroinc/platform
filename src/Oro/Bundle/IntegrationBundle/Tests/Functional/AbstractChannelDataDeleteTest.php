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

    protected function setUp()
    {
        $this->initClient(
            array(),
            array_merge($this->generateBasicAuthHeader(), array('HTTP_X-CSRF-Header' => 1))
        );
        $this->container = $this->client->getKernel()->getContainer();
        $this->em = $this->container->get('doctrine.orm.entity_manager');
    }

    /**
     * Create related entity
     *
     * @param Channel $channel
     */
    abstract protected function createRelatedEntity(Channel $channel);
}
