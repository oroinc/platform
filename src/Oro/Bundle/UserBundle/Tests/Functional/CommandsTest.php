<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\UserBundle\Command\CreateUserCommand;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\Kernel;

use Symfony\Component\Console\Application;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
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

    public function testCreateUser()
    {
        $objectManager = $this->getContainer()->get('doctrine')->getManager();
        $userManager = $this->getContainer()->get('oro_user.manager');

        $organizationsData = [
            'org1' => ['name' => 'org1_' . uniqid()],
            'org2' => ['name' => 'org2_' . uniqid()],
            'org3' => ['name' => 'org3_' . uniqid()],
        ];

        $businessUnitName = 'bu1' . uniqid();

        /** @var Organization[] $organizations */
        $organizations = [];

        foreach ($organizationsData as $key => $organizationData) {
            $organizations[$key] = $organization = new Organization();
            $organizations[$key]
                ->setName($organizationData['name'])
                ->setEnabled(true)
            ;
            $objectManager->persist($organization);
        }

        $businessUnit = new BusinessUnit();
        $businessUnit
            ->setName($businessUnitName)
            ->setOrganization($organizations['org1'])
        ;
        $objectManager->persist($businessUnit);

        $objectManager->flush();

        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($this->client->getKernel());
        $application->setAutoExit(false);
        $application->add(new CreateUserCommand());

        $command = $application->find('oro:user:create');
        $commandTester = new CommandTester($command);

        $userName = 'user_' . uniqid();
        $arguments = [
            'command'               => $command->getName(),
            '--user-business-unit'  => $businessUnitName,
            '--user-name'           => $userName,
            '--user-email'          => $userName . '@mailinator.com',
            '--user-firstname'      => 'UserF',
            '--user-lastname'       => 'UserL',
            '--user-password'       => 'admin',
            '--user-organization'   => [
                $organizationsData['org2']['name'],
                $organizationsData['org3']['name'],
            ]
        ];

        $commandTester->execute($arguments);

        $this->assertEmpty($commandTester->getDisplay());
        /** @var User $user */
        $user = $userManager->findUserByUsername($userName);
        $this->assertSame($user->getOrganization(), $organizations['org1']);
        $this->assertContains($businessUnit, $user->getBusinessUnits());
        $this->assertTrue($user->isEnabled());
        $this->assertEquals($userName, $user->getUsername());
        $this->assertEquals($userName . '@mailinator.com', $user->getEmail());
        $this->assertEquals('UserF', $user->getFirstName());
        $this->assertEquals('UserL', $user->getLastName());
        $userOrganizations = $user->getOrganizations();
        $this->assertContains($organizations['org2'], $userOrganizations);
        $this->assertContains($organizations['org3'], $userOrganizations);

        $userName = 'user_' . uniqid();
        $arguments['--user-name'] = $userName;
        $arguments['--user-email'] = $userName . '@mailinator.com';
        $arguments['--user-organization'] = [uniqid()];

        $commandTester->execute($arguments);

        $this->assertStringStartsWith('Invalid Organization: ', $commandTester->getDisplay());
    }

    public function testGenerateWsse()
    {
        /** @var Kernel $kernel */
        $kernel = $this->client->getKernel();

        /** @var Application $application */
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
        $application->setAutoExit(false);

        $doctrine =$this->client
            ->getContainer()
            ->get('doctrine');
        /** @var Organization $organization */
        $organization = $doctrine->getRepository('OroOrganizationBundle:Organization')
            ->getFirst();
        $user = $this->client
            ->getContainer()
            ->get('oro_user.manager')
            ->findUserByUsername('admin');
        $apiKey = $doctrine->getRepository('OroUserBundle:UserApi')->findOneBy(
            ['user' => $user, 'organization' => $organization]
        );
        
        static::assertInstanceOf('Oro\Bundle\UserBundle\Entity\UserApi', $apiKey, '$apiKey is not an object');

        $command = new GenerateWSSEHeaderCommand();
        $command->setApplication($application);
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--env' => $kernel->getEnvironment(),
                'apiKey' => $apiKey->getApiKey(),
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
