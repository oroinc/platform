<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Tests\Functional\Commands;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\WsseAuthenticationBundle\Command\DeleteNoncesCommand;
use Oro\Bundle\WsseAuthenticationBundle\Command\GenerateWsseHeaderCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;

class CommandsTest extends WebTestCase
{
    private const FIREWALL_NAME = 'wsse_secured';

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testGenerateWsse(): array
    {
        /** @var Kernel $kernel */
        $kernel = $this->client->getKernel();

        $container = $this->client->getContainer();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $doctrine = $container->get('doctrine');

        /** @var Organization $organization */
        $organization = $doctrine->getRepository(Organization::class)->getFirst();
        $user = $container->get('oro_user.manager')->findUserByUsername('admin');
        $apiKey = $doctrine->getRepository(UserApi::class)
            ->findOneBy(['user' => $user, 'organization' => $organization]);

        self::assertInstanceOf(UserApi::class, $apiKey, '$apiKey is not an object');

        $command = new GenerateWSSEHeaderCommand(
            $doctrine,
            $this->getContainer()->get('oro_wsse_authentication.service_locator.encoder')
        );
        $command->setApplication($application);
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--env' => $kernel->getEnvironment(),
            '--firewall' => self::FIREWALL_NAME,
            'apiKey' => $apiKey->getApiKey(),
        ]);

        preg_match_all('/(^Authorization:\s*(.*$))|(^X-WSSE:\s*(.*$))/im', $commandTester->getDisplay(), $header);

        return $header;
    }

    /**
     * @depends testGenerateWsse
     */
    public function testApiWithWsse(array $header): array
    {
        $response = $this->checkWsse($header);

        $this->assertJsonResponseStatusCodeEquals($response, 201);

        return $header;
    }

    private function checkWsse(array $header): Response
    {
        // Restore kernel after console command.
        $this->client->getKernel()->boot();

        $request = $this->prepareData();
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_user'),
            $request,
            [],
            [
                'HTTP_Authorization' => $header[2][0],
                'HTTP_X-WSSE' => $header[4][1]
            ]
        );

        return $this->client->getResponse();
    }

    private function prepareData(): array
    {
        return [
            'user' => [
                'username' => 'user_' . mt_rand(),
                'email' => 'test_' . mt_rand() . '@test.com',
                'enabled' => '1',
                'plainPassword' => '1231231q',
                'firstName' => 'firstName',
                'lastName' => 'lastName',
                'userRoles' => ['3'],
                'owner' => '1',
            ],
        ];
    }

    /**
     * @depends testApiWithWsse
     */
    public function testDeleteNonces(array $header): array
    {
        $nonceCache = $this->getNonceCache(self::FIREWALL_NAME);

        $this->assertTrue($nonceCache->hasItem($this->getNonceCacheKey($this->getNonce($header[4][1]))));

        /** @var Kernel $kernel */
        $kernel = $this->client->getKernel();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $command = new DeleteNoncesCommand(
            $this->getContainer()->get('oro_wsse_authentication.service_locator.nonce_cache')
        );
        $command->setApplication($application);
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--env' => $kernel->getEnvironment(),
            '--firewall' => self::FIREWALL_NAME,
        ]);

        $this->assertFalse($nonceCache->hasItem($this->getNonceCacheKey($this->getNonce($header[4][1]))));
        self::assertStringContainsString('Deleted nonce cache', $commandTester->getDisplay());

        return $header;
    }

    private function getNonceCache(string $firewallName): AdapterInterface
    {
        $nonceCacheServiceLocator = $this->getContainer()->get('oro_wsse_authentication.service_locator.nonce_cache');

        return $nonceCacheServiceLocator->get('oro_wsse_authentication.nonce_cache.' . $firewallName);
    }

    private function getNonce(string $wsseHeader): ?string
    {
        preg_match('/Nonce="([^"]+)"/', $wsseHeader, $matches);

        return $matches[1] ?? null;
    }

    private function getNonceCacheKey(string $nonce): string
    {
        $key = preg_replace('/[^a-zA-Z0-9_.]/', '_', $nonce);
        if (strlen($key) > 64) {
            $key = md5($key);
        }

        return $key;
    }
}
