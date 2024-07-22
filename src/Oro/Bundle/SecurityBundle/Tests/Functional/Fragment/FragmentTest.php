<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Fragment;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Controller\UserController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\UriSigner;

class FragmentTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private UriSigner $signer;

    protected function setUp(): void
    {
        $this->initClient();

        $secret = self::getContainer()->getParameter('secret');
        $this->signer = new UriSigner($secret);
    }

    public function testViewUserProfileUsingFragment(): void
    {
        $secretUrl = self::assertSecretUrl([
            '_path' => sprintf('_controller=%s::viewProfileAction', UserController::class)
        ]);
        $this->client->request('GET', $secretUrl);

        // Expect HTTP_NOT_FOUND instead of HTTP_UNAUTHORIZED, HTTP_INTERNAL_SERVER_ERROR, etc.
        self::assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testExecuteSystemCommandUsingFragment(): void
    {
        $secretUrl = self::assertSecretUrl([
            '_path' => '_controller=system&command=echo "Executable command"&return_value=null&_route=oro_user_view',
        ]);

        ob_start();
        $this->client->request('GET', $secretUrl);
        $systemContent = ob_get_contents();
        ob_end_clean();
        self::assertStringNotContainsString('Executable command', $systemContent);
    }

    private function assertSecretUrl(array $parameters): string
    {
        $applicationUrl = self::getConfigManager(null)->get('oro_ui.application_url');

        return $this->signer->sign(sprintf('%s/_fragment?%s', $applicationUrl, http_build_query($parameters)));
    }
}
