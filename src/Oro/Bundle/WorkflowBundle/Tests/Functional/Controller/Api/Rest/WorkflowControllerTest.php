<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @db_isolation
 * @db_reindex
 */
class WorkflowControllerTest extends WebTestCase
{
    static protected $fixturesLoaded = false;

    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        parent::setUp();

        $this->client = static::createClient(array(), ToolsAPI::generateWsseHeader());
        if (!self::$fixturesLoaded) {
            $prev = '..' . DIRECTORY_SEPARATOR;
            $path = __DIR__ . DIRECTORY_SEPARATOR . $prev . $prev . $prev . 'DataFixtures';
            $this->client->appendFixtures($path);
            self::$fixturesLoaded = true;
        }
    }

    public function testDeleteAction()
    {
        $manager = $this->client->getContainer()->get('doctrine')->getManager();
        $entity = $manager->getRepository('OroTestFrameworkBundle:WorkflowAwareEntity')->findAll();
        $this->assertNotEmpty($entity);
    }
}
