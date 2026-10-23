<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Tests\Functional\EventListener;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserACLData;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests that error and redirect responses have the same security headers as a regular page.
 */
final class SecurityHeadersTest extends WebTestCase
{
    private const array SECURITY_HEADERS = ['X-Frame-Options', 'X-Content-Type-Options', 'X-XSS-Protection'];

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    private function getRegularPageSecurityHeaders(): array
    {
        $this->client->request('GET', $this->getUrl('oro_user_index'));
        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);
        $headers = $this->getSecurityHeaders($response);
        foreach ($headers as $name => $value) {
            self::assertNotNull($value, sprintf('The "%s" header is missing.', $name));
        }

        return $headers;
    }

    private function getSecurityHeaders(Response $response): array
    {
        $headers = [];
        foreach (self::SECURITY_HEADERS as $name) {
            $headers[$name] = $response->headers->get($name);
        }

        return $headers;
    }

    public function testNotFoundPage(): void
    {
        $expectedHeaders = $this->getRegularPageSecurityHeaders();

        $this->client->request('GET', $this->getUrl('oro_default') . 'non-existing-url-bb27956');
        $response = $this->client->getResponse();

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        self::assertSame($expectedHeaders, $this->getSecurityHeaders($response));
    }

    public function testForbiddenPage(): void
    {
        $this->loadFixtures([LoadUserACLData::class]);
        $expectedHeaders = $this->getRegularPageSecurityHeaders();

        $this->loginUser(LoadUserACLData::SIMPLE_USER_ROLE_LOCAL);
        try {
            $this->client->request('GET', $this->getUrl('oro_business_unit_index'));
            $response = $this->client->getResponse();
        } finally {
            $this->loginUser('');
        }

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
        self::assertSame($expectedHeaders, $this->getSecurityHeaders($response));
    }

    public function testRedirect(): void
    {
        $expectedHeaders = $this->getRegularPageSecurityHeaders();

        $this->client->request('GET', $this->getUrl('oro_user_security_login'));
        $response = $this->client->getResponse();

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FOUND);
        self::assertStringEndsWith($this->getUrl('oro_default'), $response->headers->get('Location'));
        self::assertSame($expectedHeaders, $this->getSecurityHeaders($response));
    }

    public function testHashNavigationRedirect(): void
    {
        $expectedHeaders = $this->getRegularPageSecurityHeaders();

        $this->client->request(
            'GET',
            $this->getUrl('oro_user_security_login'),
            [],
            [],
            ['HTTP_' . strtoupper(str_replace('-', '_', ResponseHashnavListener::HASH_NAVIGATION_HEADER)) => 1]
        );
        $response = $this->client->getResponse();

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, 'application/json');
        self::assertSame($expectedHeaders, $this->getSecurityHeaders($response));
    }
}
