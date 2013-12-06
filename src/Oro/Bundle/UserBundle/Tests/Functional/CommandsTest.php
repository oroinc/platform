<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\Kernel;

use Symfony\Component\Console\Application;
use Oro\Bundle\UserBundle\Command\GenerateWSSEHeaderCommand;

/**
 * @outputBuffering enabled
 * @db_isolation
 * @db_reindex
 */
class CommandsTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient();
    }

    public function testGenerateWsse()
    {
        /** @var Kernel $kernel */
        $kernel = $this->client->getKernel();

        /** @var Application $application */
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
        $application->setAutoExit(false);

        $command = new GenerateWSSEHeaderCommand();
        $command->setApplication($application);
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), 'username' => 'admin'));

        preg_match_all('/(^Authorization:\s*(.*$))|(^X-WSSE:\s*(.*$))/im', $commandTester->getDisplay(), $header);

        return $header;
    }

    /**
     * @depends testGenerateWsse
     * @param array $header
     */
    public function testApiWithWSSE($header)
    {
        //restore kernel after console command
        $this->client->getKernel()->boot();
        $request = $this->prepareData();
        $this->client->request(
            'POST',
            $this->client->generate('oro_api_post_user'),
            $request,
            array(),
            array(
                'HTTP_Authorization' => $header[2][0],
                'HTTP_X-WSSE' => $header[4][1]
            )
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 201);
    }

    protected function prepareData()
    {
        return array(
            "user" => array (
                "username" => 'user_' . mt_rand(),
                "email" => 'test_'  . mt_rand() . '@test.com',
                "enabled" => '1',
                "plainPassword" => '1231231q',
                "firstName" => "firstName",
                "lastName" => "lastName",
                "rolesCollection" => array("3"),
                "owner" => "1",
            )
        );
    }
}
