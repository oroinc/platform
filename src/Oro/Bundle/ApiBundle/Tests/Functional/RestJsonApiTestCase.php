<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;

class RestJsonApiTestCase extends ApiTestCase
{
    const JSON_API_CONTENT_TYPE = 'application/vnd.api+json';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => self::JSON_API_CONTENT_TYPE]
            )
        );

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestType()
    {
        return new RequestType([RequestType::REST, RequestType::JSON_API]);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $parameters
     *
     * @return Response
     */
    protected function request($method, $uri, array $parameters = [])
    {
        if (isset($parameters['filter'])) {
            array_walk($parameters['filter'], function (&$item) {
                $item = self::processTemplateData($item);
                $item = is_array($item) ? implode(',', $item) : $item;
            });
        }

        $this->client->request(
            $method,
            $uri,
            $parameters,
            [],
            ['CONTENT_TYPE' => self::JSON_API_CONTENT_TYPE]
        );

        return $this->client->getResponse();
    }

    /**
     * @param array $routeParameters
     * @param array $parameters
     * @return null|Response
     */
    protected function get(array $routeParameters = [], array $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_get', $routeParameters),
            $parameters
        );

        $entityType = isset($parameters['entity']) ? $parameters['entity'] : 'unknown';
        $this->assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * @param array $routeParameters
     * @param array $parameters
     * @return null|Response
     */
    protected function getRelationship(array $routeParameters = [], array $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_get_relationship', $routeParameters),
            $parameters
        );

        $entityType = isset($parameters['entity']) ? $parameters['entity'] : 'unknown';
        $this->assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * @param array $routeParameters
     * @param array $parameters
     * @return null|Response
     */
    protected function getSubresource(array $routeParameters = [], array $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_get_subresource', $routeParameters),
            $parameters
        );

        $entityType = isset($parameters['entity']) ? $parameters['entity'] : 'unknown';
        $this->assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * @param array $routeParameters
     * @param array $parameters
     * @return null|Response
     */
    protected function cget(array $routeParameters = [], array $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', $routeParameters),
            $parameters
        );

        $entityType = isset($parameters['entity']) ? $parameters['entity'] : 'unknown';
        $this->assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * @param array $routeParameters
     * @param array $parameters
     * @return Response
     */
    protected function post(array $routeParameters = [], $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', $routeParameters),
            $parameters
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * @param array $routeParameters
     * @param array $parameters
     * @return Response
     */
    protected function patch(array $routeParameters = [], $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch',
                $routeParameters
            ),
            $parameters
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_OK);

        return $response;
    }

    /**
     * Compare response content with expected data
     *
     * @param array|string $expectedContent Can be path to yml template file or array
     * @param Response     $response
     * @param object|null  $entity If not null, object will set as entity reference
     */
    protected function assertResponseContains($expectedContent, Response $response, $entity = null)
    {
        if ($entity) {
            $this->getReferenceRepository()->addReference('entity', $entity);
        }

        $content = json_decode($response->getContent(), true);

        if (is_string($expectedContent)) {
            $file = $this->getContainer()->get('file_locator')->locate($expectedContent);
            self::assertTrue(is_file($file), sprintf('File "%s" with expected content not found', $expectedContent));

            $expectedContent = Yaml::parse(file_get_contents($file));
        }

        self::assertArrayContains(
            self::processTemplateData($expectedContent),
            $content
        );
    }

    /**
     * @param string   $filename Full path to file
     * @param Response $response
     */
    protected function dumpYmlTemplate($filename, Response $response)
    {
        $data = json_decode($response->getContent(), true);
        $references = $this->getReferenceRepository()->getReferences();
        $propertyAccessor = new PropertyAccessor();
        $idReferences = [];

        foreach ($references as $id => $reference) {
            try {
                $referenceId = $propertyAccessor->getValue($reference, 'id');
                $idReferences[$referenceId] = $id;
            } catch (\Exception $e) {
            }
        }

        array_walk_recursive($data, function (&$item, $key) use ($idReferences) {
            if ($key === 'id') {
                if (isset($idReferences[(int)$item])) {
                    $item = '@'.$idReferences[$item].'->id';
                }
            }
        });

        file_put_contents(
            __DIR__.'/responses/'.$filename,
            Yaml::dump($data, 8)
        );
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param ResponseHeaderBag $headers
     *
     * @return bool
     */
    protected static function isApplicableContentType(ResponseHeaderBag $headers)
    {
        return $headers->contains('Content-Type', self::JSON_API_CONTENT_TYPE);
    }
}
