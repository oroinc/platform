<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Command\ImpersonateUserCommand;
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
        return $this->doExecuteCommand(ImpersonateUserCommand::getDefaultName(), [
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
                ImpersonateUserCommand::getDefaultName()
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
}
