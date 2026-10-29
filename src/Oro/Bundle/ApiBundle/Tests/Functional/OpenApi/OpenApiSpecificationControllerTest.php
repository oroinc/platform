<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Tests\Functional\OpenApi;

use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class OpenApiSpecificationControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testDeleteDoesNotAllowGetRequest(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            $this->getUrl('oro_openapi_specification_delete', ['id' => 1])
        );

        self::assertResponseStatusCodeEquals(
            $this->client->getResponse(),
            Response::HTTP_METHOD_NOT_ALLOWED
        );
    }

    public function testViewEscapesServerUrls(): void
    {
        $this->loadFixtures([LoadUser::class]);
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        $specification = new OpenApiSpecification();
        $specification->setOwner($user);
        $specification->setOrganization($user->getOrganization());
        $specification->setName('Test OpenAPI Spec');
        $specification->setFormat('json');
        $specification->setView('rest_json_api');
        $specification->setEntities(['organizations']);
        $specification->setServerUrls(['https://a.test/"><img src=x>', 'https://b.test/api']);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OpenApiSpecification::class);
        $em->persist($specification);
        $em->flush();

        $crawler = $this->client->request(
            Request::METHOD_GET,
            $this->getUrl('oro_openapi_specification_view', ['id' => $specification->getId()])
        );

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
        self::assertCount(0, $crawler->filter('.responsive-block img'));
        self::assertStringContainsString(
            'https://a.test/&quot;&gt;&lt;img src=x&gt;<br />' . "\n" . 'https://b.test/api',
            $this->client->getResponse()->getContent()
        );
    }

    public function testViewEscapesEntities(): void
    {
        $this->loadFixtures([LoadUser::class]);
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        $specification = new OpenApiSpecification();
        $specification->setOwner($user);
        $specification->setOrganization($user->getOrganization());
        $specification->setName('Test OpenAPI Spec');
        $specification->setFormat('json');
        $specification->setView('rest_json_api');
        $specification->setEntities(['organizations', 'x\\<img src=x onerror=alert(1)>']);
        $specification->setServerUrls(['https://a.test/api']);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OpenApiSpecification::class);
        $em->persist($specification);
        $em->flush();

        $crawler = $this->client->request(
            Request::METHOD_GET,
            $this->getUrl('oro_openapi_specification_view', ['id' => $specification->getId()])
        );

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
        self::assertCount(0, $crawler->filter('.responsive-block img'));
        self::assertStringContainsString(
            '</span>; <span class="nowrap">&lt;img src=x onerror=alert(1)&gt;</span>',
            $this->client->getResponse()->getContent()
        );
    }
}
