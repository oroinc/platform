<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\UserLoginAttempt;
use Oro\Bundle\UserBundle\Security\ImpersonationAuthenticator;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Component\Testing\Command\CommandOutputNormalizer;
use Oro\Component\Testing\Command\CommandTestingTrait;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group regression
 */
class UserImpersonationTest extends WebTestCase
{
    use CommandTestingTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->client->followRedirects();
        $this->loadFixtures([LoadUserData::class]);
    }

    private function runUserImpersonateCommand(string $username, string $route): CommandTester
    {
        return $this->doExecuteCommand('oro:user:impersonate', [
            'username' => $username,
            '--route' => $route
        ]);
    }

    public function testLinkGeneratedByCommandCanBeUsedToLogin()
    {
        $commandTester = $this->runUserImpersonateCommand(LoadUserData::SIMPLE_USER, 'oro_user_profile_view');

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, 'open the following URL');

        $output = CommandOutputNormalizer::toSingleLine($commandTester->getDisplay());

        $urlPattern = '/http.+' . ImpersonationAuthenticator::TOKEN_PARAMETER . '=[[:alnum:]]+/';
        $matches = [];
        if (1 !== preg_match($urlPattern, $output, $matches)) {
            $this->fail(sprintf(
                'Cannot find URL in the output of the %s command',
                'oro:user:impersonate'
            ));
        }
        $url = $matches[0];

        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // we should be on the user profile view page being logged in as the test user
        $this->assertSelectorTextSame(
            '.page-title__entity-title',
            LoadUserData::SIMPLE_USER_FIRST_NAME . ' ' . LoadUserData::SIMPLE_USER_LAST_NAME
        );
    }

    public function testImpersonationLoginAttemptIsTracked(): void
    {
        $commandTester = $this->doExecuteCommand('oro:user:impersonate', [
            'username' => LoadUserData::SIMPLE_USER,
            '--no-notification' => true
        ]);

        $output = $commandTester->getDisplay();

        self::assertSame(
            1,
            preg_match(
                '/https?:\/\/[^\s]+_impersonation_token=[a-z0-9]+/',
                $output,
                $matches
            )
        );

        $this->client->request('GET', $matches[0]);

        $this->assertHtmlResponseStatusCodeEquals(
            $this->client->getResponse(),
            200
        );

        $loginAttempt = $this->getContainer()
            ->get('doctrine')
            ->getRepository(UserLoginAttempt::class)
            ->findOneBy(
                ['username' => LoadUserData::SIMPLE_USER],
                ['attemptAt' => 'DESC']
            );

        self::assertNotNull($loginAttempt);
        self::assertTrue($loginAttempt->isSuccess());
        self::assertSame(
            $this->getContainer()->getParameter('oro_user.login_sources')['impersonation']['code'],
            $loginAttempt->getSource()
        );
    }
}
