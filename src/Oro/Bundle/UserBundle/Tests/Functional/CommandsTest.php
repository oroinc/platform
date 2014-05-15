<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\Kernel;

use Symfony\Component\Console\Application;

use Oro\Bundle\UserBundle\Command\GenerateWSSEHeaderCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class CommandsTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
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
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--env' => $kernel->getEnvironment(),
                'username' => 'admin'
            )
        );

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
            $this->getUrl('oro_api_post_user'),
            $request,
            array(),
            array(
                'HTTP_Authorization' => $header[2][0],
                'HTTP_X-WSSE' => $header[4][1]
            )
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 201);
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
                "roles" => array("3"),
                "owner" => "1"
            )
        );
    }
}
