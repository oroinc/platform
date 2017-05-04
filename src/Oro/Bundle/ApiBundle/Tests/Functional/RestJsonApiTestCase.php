<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\ApiBundle\Request\RequestType;

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
     * Sends REST API request.
     *
     * @param string $method
     * @param string $uri
     * @param array  $parameters
     *
     * @return Response
     */
    protected function request($method, $uri, array $parameters = [])
    {
        if (!empty($parameters['filter'])) {
            foreach ($parameters['filter'] as $key => $filter) {
                $filter = self::processTemplateData($filter);
                if (is_array($filter)) {
                    $filter = implode(',', $filter);
                }
                $parameters['filter'][$key] = $filter;
            }
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
     * Sends GET request for a single entity.
     *
     * @param array $routeParameters
     * @param array $parameters
     *
     * @return Response
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

        $entityType = $this->extractEntityType($routeParameters);
        self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * Sends GET request for a relationship of a single entity.
     *
     * @param array $routeParameters
     * @param array $parameters
     *
     * @return Response
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

        $entityType = $this->extractEntityType($routeParameters);
        self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get relationship');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * Sends GET request for a sub-resource of a single entity.
     *
     * @param array $routeParameters
     * @param array $parameters
     *
     * @return Response
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

        $entityType = $this->extractEntityType($routeParameters);
        self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get subresource');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * Sends GET request for a list of entities.
     *
     * @param array $routeParameters
     * @param array $parameters
     *
     * @return Response
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

        $entityType = $this->extractEntityType($routeParameters);
        self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * Sends POST request for an entity resource.
     *
     * @param array        $routeParameters
     * @param array|string $parameters
     *
     * @return Response
     */
    protected function post(array $routeParameters = [], $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        if (is_string($parameters) && $this->isRelativePath($parameters)) {
            $parameters = $this->getTestResourcePath('requests', $parameters);
        }
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', $routeParameters),
            $parameters
        );

        $this->getEntityManager()->clear();

        $entityType = $this->extractEntityType($routeParameters);
        self::assertApiResponseStatusCodeEquals($response, Response::HTTP_CREATED, $entityType, 'post');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * Sends POST request for a relationship of a single entity.
     *
     * @param array $routeParameters
     * @param array $parameters
     *
     * @return Response
     */
    protected function postRelationship(array $routeParameters = [], array $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post_relationship', $routeParameters),
            $parameters
        );

        $this->getEntityManager()->clear();

        $entityType = $this->extractEntityType($routeParameters);
        self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'post relationship');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * Sends PATCH request for a single entity.
     *
     * @param array        $routeParameters
     * @param array|string $parameters
     *
     * @return Response
     */
    protected function patch(array $routeParameters = [], $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        if (is_string($parameters) && $this->isRelativePath($parameters)) {
            $parameters = $this->getTestResourcePath('requests', $parameters);
        }
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch',
                $routeParameters
            ),
            $parameters
        );

        $this->getEntityManager()->clear();

        $entityType = $this->extractEntityType($routeParameters);
        self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'patch');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * Sends PATCH request for a relationship of a single entity.
     *
     * @param array $routeParameters
     * @param array $parameters
     *
     * @return Response
     */
    protected function patchRelationship(array $routeParameters = [], array $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_patch_relationship', $routeParameters),
            $parameters
        );

        $this->getEntityManager()->clear();

        $entityType = $this->extractEntityType($routeParameters);
        self::assertApiResponseStatusCodeEquals(
            $response,
            Response::HTTP_NO_CONTENT,
            $entityType,
            'patch relationship'
        );

        return $response;
    }

    /**
     * Sends DELETE request for a single entity.
     *
     * @param array $routeParameters
     * @param array $parameters
     *
     * @return Response
     */
    protected function delete(array $routeParameters = [], array $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_api_delete', $routeParameters),
            $parameters
        );

        $this->getEntityManager()->clear();

        $entityType = $this->extractEntityType($routeParameters);
        self::assertApiResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT, $entityType, 'delete');

        return $response;
    }

    /**
     * Sends DELETE request for a list of entities.
     *
     * @param array $routeParameters
     * @param array $parameters
     *
     * @return Response
     */
    protected function cdelete(array $routeParameters = [], array $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_api_cdelete', $routeParameters),
            $parameters
        );

        $this->getEntityManager()->clear();

        $entityType = $this->extractEntityType($routeParameters);
        self::assertApiResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT, $entityType, 'delete list');

        return $response;
    }

    /**
     * Asserts the response content contains the the given data.
     *
     * @param array|string $expectedContent The file name or full file path to yml template file or array
     * @param Response     $response
     * @param object|null  $entity          If not null, object will set as entity reference
     */
    protected function assertResponseContains($expectedContent, Response $response, $entity = null)
    {
        if ($entity) {
            $this->getReferenceRepository()->addReference('entity', $entity);
        }

        $content = json_decode($response->getContent(), true);

        if (is_string($expectedContent)) {
            if ($this->isRelativePath($expectedContent)) {
                $expectedContent = $this->getTestResourcePath('responses', $expectedContent);
            }
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
     * Asserts the response contains the given number of data items.
     *
     * @param int      $expectedCount
     * @param Response $response
     */
    protected function assertResponseCount($expectedCount, Response $response)
    {
        $content = json_decode($response->getContent(), true);
        $this->assertCount($expectedCount, $content['data']);
    }

    /**
     * Asserts the response data are not empty.
     *
     * @param Response $response
     */
    protected function assertResponseNotEmpty(Response $response)
    {
        $content = json_decode($response->getContent(), true);
        $this->assertNotEmpty($content['data']);
    }

    /**
     * @param string   $fileName The file name or full path to the output file
     * @param Response $response
     */
    protected function dumpYmlTemplate($fileName, Response $response)
    {
        $data = json_decode($response->getContent(), true);
        if (null === $data) {
            throw new \RuntimeException('The response does not have the content.');
        }

        $idReferences = [];
        $references = $this->getReferenceRepository()->getReferences();
        foreach ($references as $referenceId => $entity) {
            $entityClass = ClassUtils::getClass($entity);
            $entityType = $this->getEntityType($entityClass, false);
            if ($entityType) {
                $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass, false);
                if (null !== $metadata) {
                    $entityId = $metadata->getIdentifierValues($entity);
                    if (count($entityId) === 1) {
                        $entityId = (string)reset($entityId);
                        $idReferences[$entityType . '::' . $entityId] = [
                            $referenceId,
                            $metadata->getSingleIdentifierFieldName()
                        ];
                    }
                }
            }
        }

        $this->normalizeYmlTemplate($data, $idReferences);

        $content = Yaml::dump($data, 8);
        // replace "data: {}" with "data: []" to correct representation of empty collection
        $content = preg_replace('/(\s+data: ){\s*}$/m', '$1[]', $content);

        if ($this->isRelativePath($fileName)) {
            $fileName = $this->getTestResourcePath('responses', $fileName);
        }
        file_put_contents($fileName, $content);
    }

    /**
     * @param array $data
     * @param array $idReferences ['entityType::entityId' => [referenceId, entityIdFieldName], ...]
     */
    protected function normalizeYmlTemplate(array &$data, array $idReferences)
    {
        if (isset($data['type']) && isset($data['id'])) {
            $key = $data['type'] . '::' . $data['id'];
            if (isset($idReferences[$key])) {
                list($referenceId, $entityIdFieldName) = $idReferences[$key];
                $data['id'] = sprintf('<toString(@%s->%s)>', $referenceId, $entityIdFieldName);
                if (isset($data['attributes'])) {
                    $attributes = $data['attributes'];
                    $dateFields = ['createdAt', 'updatedAt', 'created', 'updated'];
                    foreach ($dateFields as $field) {
                        if (isset($attributes[$field])) {
                            $data['attributes'][$field] = sprintf(
                                '@%s->%s->format("Y-m-d\TH:i:s\Z")',
                                $referenceId,
                                $field
                            );
                        }
                    }
                }
            }
        }
        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->normalizeYmlTemplate($value, $idReferences);
            }
        }
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

    /**
     * @param array $parameters
     *
     * @return string
     */
    protected function extractEntityType(array $parameters)
    {
        if (empty($parameters['entity'])) {
            return 'unknown';
        }

        return $parameters['entity'];
    }
}
