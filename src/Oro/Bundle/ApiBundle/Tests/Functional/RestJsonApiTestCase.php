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
     * @param string $route
     * @param array $routeParameters
     * @param array $parameters
     * @return null|Response
     */
    protected function get($route, array $routeParameters = [], array $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'GET',
            $this->getUrl($route, $routeParameters),
            $parameters
        );

        $entityType = isset($parameters['entity']) ? $parameters['entity'] : 'unknown';
        $this->assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * @param string $route
     * @param array $routeParameters
     * @param array $parameters
     * @return Response
     */
    protected function post($route, array $routeParameters = [], $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'POST',
            $this->getUrl($route, $routeParameters),
            $parameters
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

        return $response;
    }

    /**
     * @param string $route
     * @param array $routeParameters
     * @param array $parameters
     * @return Response
     */
    protected function patch($route, array $routeParameters = [], $parameters = [])
    {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'PATCH',
            $this->getUrl(
                $route,
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
            self::getReferenceRepository()->addReference('entity', $entity);
        }

        $content = json_decode($response->getContent(), true);

        if (is_string($expectedContent)) {
            $file = $this->getContainer()->get('file_locator')->locate($expectedContent);
            self::assertTrue(is_file($file), sprintf('File "%s" with expected content not found', $expectedContent));

            $expectedContent = Yaml::parse(file_get_contents($file));
        }

        self::assertIsContained(
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
            if ($key == 'id') {
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
     * @param array $expected
     * @param array $content
     * @param array $path
     * @param int   $deep
     * @param bool  $repeatedly
     */
    protected static function assertIsContained(
        array $expected,
        array $content,
        $path = [],
        $deep = 0,
        $repeatedly = false
    ) {
        $deep++;
        foreach ($expected as $key => $value) {
            $path[$deep] = $key;
            self::assertArrayHasKey($key, $content);
            if (is_array($value)) {
                if (is_int($key)) {
                    try {
                        self::assertIsContained($value, $content[$key], $path, $deep);
                    } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
                        if ($repeatedly) {
                            throw $e;
                        }
                        //this can happen cause prostgres and mysql return elements in different order
                        //find element path for failed element
                        preg_match('/Failed assert "(?P<path>.*)" response property/', $e->getMessage(), $matches);
                        $elementPath = explode('.', $matches['path']);
                        $relatedPath = array_slice($elementPath, count($path));

                        //sort array by key that assertIsContained was failed
                        self::sortByNestedValue($expected, $relatedPath);
                        self::sortByNestedValue($content, $relatedPath);

                        //try again with sorted elements
                        array_pop($path);
                        $deep--;

                        self::assertIsContained($expected, $content, $path, $deep, true);
                    }
                } else {
                    self::assertIsContained($value, $content[$key], $path, $deep);
                }
            } else {
                self::assertSame(
                    $value,
                    $content[$key],
                    sprintf('Failed assert "%s" response property', implode('.', $path))
                );
            }
        }
    }

    /**
     * @param array $sourse
     * @param array $relatedPath
     */
    protected static function sortByNestedValue(array &$sourse, array $relatedPath)
    {
        usort($sourse, function ($item1, $item2) use ($relatedPath) {
            $value1 = self::getNestedElement($item1, $relatedPath);
            $value2 = self::getNestedElement($item2, $relatedPath);
            $onlyNumbersRegexp = '/^\d+$/';

            if (preg_match($onlyNumbersRegexp, $value1) && preg_match($onlyNumbersRegexp, $value2)) {
                $value1 = (int) $value1;
                $value2 = (int) $value2;
            }

            if ($value1 == $value2) {
                return 0;
            };

            return $value1 < $value2 ? -1 : 1;
        });
    }

    /**
     * Example:
     * $path = [2, 'id'];
     * $source = [
     *    ['id' => 'a'],
     *    ['id' => 'b'],
     *    ['id' => 'c'],
     *    ['id' => 'd'],
     * ];
     * echo $this->getNestedElement($source, $path); // "c"
     * @param array $source
     * @param array $path
     * @return mixed
     */
    protected static function getNestedElement(array $source, array $path)
    {
        return array_reduce($path, function ($carry, $key) {
            return $carry[$key];
        }, $source);
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
