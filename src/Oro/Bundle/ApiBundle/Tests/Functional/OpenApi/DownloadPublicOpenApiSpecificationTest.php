<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\OpenApi;

use Oro\Bundle\ApiBundle\Async\Topic\CreateOpenApiSpecificationTopic;
use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class DownloadPublicOpenApiSpecificationTest extends WebTestCase
{
    use MessageQueueExtension;

    private OpenApiSpecification $specification;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUser::class]);
    }

    #[\Override]
    protected function postFixtureLoad(): void
    {
        $this->specification = $this->createOpenApiSpecification();
    }

    private function createOpenApiSpecification(): OpenApiSpecification
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        $specification = new OpenApiSpecification();
        $specification->setOwner($user);
        $specification->setOrganization($user->getOrganization());
        $specification->setName('Test OpenAPI Spec');
        $specification->setPublicSlug('test-open-api-spec');
        $specification->setFormat('yaml');
        $specification->setView('rest_json_api');
        $specification->setEntities(['organizations']);
        $specification->setPublished(true);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OpenApiSpecification::class);
        $em->persist($specification);
        $em->flush();

        self::sendMessage(CreateOpenApiSpecificationTopic::getName(), ['entityId' => $specification->getId()]);
        $this->consumeAllMessages(CreateOpenApiSpecificationTopic::getName());

        return $specification;
    }

    public function testDownload(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            $this->getUrl(
                'oro_public_openapi_specification_download',
                [
                    'organizationId' => $this->specification->getOrganization()->getId(),
                    'slug' => $this->specification->getPublicSlug()
                ]
            )
        );
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function testDownloadWithCors(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            $this->getUrl(
                'oro_public_openapi_specification_download',
                [
                    'organizationId' => $this->specification->getOrganization()->getId(),
                    'slug' => $this->specification->getPublicSlug()
                ]
            ),
            [],
            [],
            ['HTTP_Origin' => 'https://api.test.com']
        );
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertEquals('https://api.test.com', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testDownloadWithCorsForNotExistingSpecification(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            $this->getUrl(
                'oro_public_openapi_specification_download',
                [
                    'organizationId' => $this->specification->getOrganization()->getId(),
                    'slug' => 'not-existing'
                ]
            ),
            [],
            [],
            ['HTTP_Origin' => 'https://api.test.com']
        );
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        self::assertEquals('https://api.test.com', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testGetOptions(): void
    {
        $this->client->request(
            Request::METHOD_OPTIONS,
            $this->getUrl('oro_public_openapi_specification_download_options')
        );
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertEquals('OPTIONS, GET', $response->headers->get('Allow'));
        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function testGetOptionsWithCors(): void
    {
        $this->client->request(
            Request::METHOD_OPTIONS,
            $this->getUrl('oro_public_openapi_specification_download_options'),
            [],
            [],
            ['HTTP_Origin' => 'https://api.test.com']
        );
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertEquals('OPTIONS, GET', $response->headers->get('Allow'));
        self::assertEquals('https://api.test.com', $response->headers->get('Access-Control-Allow-Origin'));
        self::assertFalse($response->headers->has('Access-Control-Allow-Methods'));
        self::assertFalse($response->headers->has('Access-Control-Allow-Headers'));
        self::assertFalse($response->headers->has('Access-Control-Max-Age'));
    }

    public function testGetOptionsWithCorsAnfWithNotAllowedOrigin(): void
    {
        $this->client->request(
            Request::METHOD_OPTIONS,
            $this->getUrl('oro_public_openapi_specification_download_options'),
            [],
            [],
            ['HTTP_Origin' => 'https://another.com']
        );
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertEquals('OPTIONS, GET', $response->headers->get('Allow'));
        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
        self::assertFalse($response->headers->has('Access-Control-Allow-Methods'));
        self::assertFalse($response->headers->has('Access-Control-Allow-Headers'));
        self::assertFalse($response->headers->has('Access-Control-Max-Age'));
    }

    public function testGetOptionsWithCorsAndWithAccessControlRequestMethod(): void
    {
        $this->client->request(
            Request::METHOD_OPTIONS,
            $this->getUrl('oro_public_openapi_specification_download_options'),
            [],
            [],
            [
                'HTTP_Origin' => 'https://api.test.com',
                'HTTP_Access-Control-Request-Method' => 'POST'
            ]
        );
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertFalse($response->headers->has('Allow'));
        self::assertEquals('https://api.test.com', $response->headers->get('Access-Control-Allow-Origin'));
        self::assertEquals('OPTIONS, GET', $response->headers->get('Access-Control-Allow-Methods'));
        self::assertEquals('', $response->headers->get('Access-Control-Allow-Headers'));
        self::assertEquals('600', $response->headers->get('Access-Control-Max-Age'));
    }
}
