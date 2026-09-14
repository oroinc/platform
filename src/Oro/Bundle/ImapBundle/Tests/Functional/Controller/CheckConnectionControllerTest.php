<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\Controller;

use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadUserWithUserRoleData;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadTypedUserEmailOriginData;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;

class CheckConnectionControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader('limited_user', 'limited_user'));
        $this->loadFixtures([
            LoadUserWithUserRoleData::class,
            LoadTypedUserEmailOriginData::class
        ]);
    }

    /**
     * @dataProvider connectionCheckRouteProvider
     */
    public function testConnectionCheckRejectsAnotherUsersOriginWithoutNamedFormRoot(
        string $route,
        string $originReference
    ): void {
        /** @var UserEmailOrigin $origin */
        $origin = $this->getReference($originReference);
        $origin->setAccessToken('victim-access-token');
        $origin->setRefreshToken('victim-refresh-token');
        self::getContainer()->get('doctrine')->getManagerForClass(UserEmailOrigin::class)->flush();

        $csrfToken = $this->getCsrfToken(CsrfRequestManager::CSRF_TOKEN_ID);
        $this->client->getCookieJar()->set(
            new Cookie(CsrfRequestManager::CSRF_TOKEN_ID, $csrfToken->getValue())
        );

        $this->client->request(
            'POST',
            $this->getUrl($route),
            [
                'id' => $origin->getId(),
                'formParentName' => 'oro_user_emailsettings'
            ],
            [],
            ['HTTP_X-CSRF-Header' => $csrfToken->getValue()]
        );

        $response = $this->client->getResponse();
        self::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertStringNotContainsString('victim-access-token', $response->getContent());
        self::assertStringNotContainsString('victim-refresh-token', $response->getContent());
    }

    public function connectionCheckRouteProvider(): array
    {
        return [
            'Gmail' => [
                'oro_imap_gmail_connection_check',
                LoadTypedUserEmailOriginData::USER_EMAIL_ORIGIN_GMAIL_2
            ],
            'Microsoft' => [
                'oro_imap_microsoft_connection_check',
                LoadTypedUserEmailOriginData::USER_EMAIL_ORIGIN_MICROSOFT_2
            ]
        ];
    }
}
