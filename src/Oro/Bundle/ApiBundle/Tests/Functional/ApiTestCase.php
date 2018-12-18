<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\KernelTerminateHandler;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\TestConfigRegistry;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Assert\ArrayContainsConstraint;
use Symfony\Component\Debug\BufferingLogger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Yaml\Yaml;

/**
 * The base class for Data API functional tests.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class ApiTestCase extends WebTestCase
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var bool */
    private $isKernelRebootDisabled = false;

    /** @var bool */
    private $isKernelTerminateHandlerDisabled = false;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $container = self::getContainer();
        $this->valueNormalizer = $container->get('oro_api.value_normalizer');
        $this->doctrineHelper = $container->get('oro_api.doctrine_helper');
    }

    /**
     * Disables clearing the security token and stopping sending messages
     * during handling of "kernel.terminate" event.
     * @see initClient
     *
     * @param bool $disable
     */
    protected function disableKernelTerminateHandler(bool $disable = true)
    {
        $this->isKernelTerminateHandlerDisabled = $disable;
    }

    /**
     * {@inheritdoc}
     */
    protected function initClient(array $options = [], array $server = [], $force = false)
    {
        $client = parent::initClient($options, $server, $force);

        /**
         * Clear the security token and stop sending messages during handling of "kernel.terminate" event.
         * This is needed to prevent unexpected exceptions
         * if some database related exception occurs during handling of API request
         * (e.g. Doctrine\DBAL\Exception\UniqueConstraintViolationException).
         * As functional tests work inside a database transaction, any query to the database
         * after such exception can raise "current transaction is aborted,
         * commands ignored until end of transaction block" SQL exception
         * if PostgreSQL is used in the tests.
         */
        if (!$this->isKernelTerminateHandlerDisabled) {
            $container = $client->getKernel()->getContainer();
            $handler = new KernelTerminateHandler($container, true);
            $eventDispatcher = $container->get('event_dispatcher');
            $eventDispatcher->addListener(
                KernelEvents::TERMINATE,
                function (PostResponseEvent $event) use ($handler) {
                    $handler->onBeforeTerminate();
                },
                255
            );
            $eventDispatcher->addListener(
                KernelEvents::TERMINATE,
                function (PostResponseEvent $event) use ($handler) {
                    $handler->onAfterTerminate();
                },
                -255
            );
        }

        return $client;
    }

    /**
     * @return RequestType
     */
    abstract protected function getRequestType();

    /**
     * @param string $entityClass
     * @param bool   $throwException
     *
     * @return string
     */
    protected function getEntityType($entityClass, $throwException = true)
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $this->getRequestType(),
            $throwException
        );
    }

    /**
     * @param string $entityType
     * @param bool   $throwException
     *
     * @return string
     */
    protected function getEntityClass($entityType, $throwException = true)
    {
        return ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $this->getRequestType(),
            $throwException
        );
    }

    /**
     * @param string $entityClass
     * @param string $action
     *
     * @return bool
     */
    protected function isActionEnabled($entityClass, $action)
    {
        $resourcesProvider = self::getContainer()->get('oro_api.resources_provider');
        $excludeActions = $resourcesProvider->getResourceExcludeActions(
            $entityClass,
            Version::LATEST,
            $this->getRequestType()
        );

        return !in_array($action, $excludeActions, true);
    }

    /**
     * @return array [entity class => [entity class, [excluded action, ...]], ...]
     */
    public function getEntities()
    {
        $this->initClient();

        $result = [];
        $doctrineHelper = self::getContainer()->get('oro_api.doctrine_helper');
        $resourcesProvider = self::getContainer()->get('oro_api.resources_provider');
        $resources = $resourcesProvider->getResources(Version::LATEST, $this->getRequestType());
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();
            if (!$doctrineHelper->isManageableEntityClass($entityClass)) {
                continue;
            }
            $result[$entityClass] = [$entityClass, $resource->getExcludedActions()];
        }

        return $result;
    }

    /**
     * @return array [[entity class, association name, ApiSubresource], ...]
     */
    public function getSubresources()
    {
        $this->initClient();

        $result = [];
        $resourcesProvider = self::getContainer()->get('oro_api.resources_provider');
        $subresourcesProvider = self::getContainer()->get('oro_api.subresources_provider');
        $resources = $resourcesProvider->getResources(Version::LATEST, $this->getRequestType());
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();
            $subresources = $subresourcesProvider->getSubresources(
                $entityClass,
                Version::LATEST,
                $this->getRequestType()
            );
            if (null !== $subresources) {
                foreach ($subresources->getSubresources() as $associationName => $subresource) {
                    $result[] = [$entityClass, $associationName, $subresource];
                }
            }
        }

        return $result;
    }

    /**
     * @param string $entityClass
     *
     * @return mixed
     */
    protected function findEntityId($entityClass)
    {
        /** @var EntityManager|null $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass($entityClass);
        if (!$em) {
            return null;
        }

        $ids = $em->getRepository($entityClass)->createQueryBuilder('e')
            ->select(
                implode(
                    ',',
                    array_map(
                        function ($fieldName) {
                            return 'e.' . $fieldName;
                        },
                        $em->getClassMetadata($entityClass)->getIdentifierFieldNames()
                    )
                )
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();
        if (empty($ids)) {
            return null;
        }

        $ids = reset($ids);

        return 1 === count($ids)
            ? reset($ids)
            : $ids;
    }

    /**
     * Loads the response content and convert it to an array.
     *
     * @param string $fileName
     * @param string $folderName
     *
     * @return array
     */
    protected function loadYamlData($fileName, $folderName = null)
    {
        return Yaml::parse($this->loadData($fileName, $folderName));
    }

    /**
     * Loads the response content.
     *
     * @param string $fileName
     * @param string $folderName
     *
     * @return string
     */
    protected function loadData($fileName, $folderName = null)
    {
        if ($this->isRelativePath($fileName)) {
            $fileName = $this->getTestResourcePath($folderName, $fileName);
        }
        $file = self::getContainer()->get('file_locator')->locate($fileName);
        self::assertTrue(is_file($file), sprintf('File "%s" with expected content not found', $fileName));

        return file_get_contents($file);
    }

    /**
     * Loads the response content.
     *
     * @param array|string $expectedContent The file name or full file path to YAML template file or array
     *
     * @return array
     */
    protected function loadResponseData($expectedContent)
    {
        if (is_string($expectedContent)) {
            $expectedContent = $this->loadYamlData($expectedContent, 'responses');
        }

        return self::processTemplateData($expectedContent);
    }

    /**
     * Converts the given request to an array that can be sent to the server.
     *
     * @param array|string $request
     *
     * @return array
     */
    protected function getRequestData($request)
    {
        if (is_string($request) && $this->isRelativePath($request)) {
            $request = $this->getTestResourcePath('requests', $request);
        }

        return self::processTemplateData($request);
    }

    /**
     * Replaces all values in the given expected response content
     * with corresponding value from the actual response content
     * when the key of an element is equal to the given key
     * and the value of this element is equal to the given placeholder.
     *
     * @param array|string $expectedContent The file name or full file path to YAML template file or array
     * @param Response     $response        The response object
     * @param string       $key             The key for with a value should be updated
     * @param string       $placeholder     The marker value
     *
     * @return array
     */
    protected function updateResponseContent(
        $expectedContent,
        Response $response,
        string $key = 'id',
        string $placeholder = 'new'
    ): array {
        $expectedContent = $this->loadResponseData($expectedContent);
        $content = self::jsonToArray($response->getContent());
        $this->walkResponseContent($expectedContent, $content, $key, $placeholder);

        return $expectedContent;
    }

    /**
     * @param array  $expectedContent
     * @param array  $content
     * @param string $key
     * @param string $placeholder
     * @param array  $path
     */
    private function walkResponseContent(
        array &$expectedContent,
        array $content,
        string $key,
        string $placeholder,
        array $path = []
    ): void {
        foreach ($expectedContent as $k => &$v) {
            if ($k === $key && $v === $placeholder) {
                $contentItem = $content;
                $i = 0;
                foreach ($path as $p) {
                    if (!is_array($contentItem) || !array_key_exists($p, $contentItem)) {
                        break;
                    }
                    $contentItem = $contentItem[$p];
                    $i++;
                }
                if (count($path) === $i && is_array($contentItem) && array_key_exists($k, $contentItem)) {
                    $v = $contentItem[$k];
                }
            } elseif (is_array($v)) {
                $this->walkResponseContent(
                    $expectedContent[$k],
                    $content,
                    $key,
                    $placeholder,
                    array_merge($path, [$k])
                );
            }
        }
    }

    /**
     * @param Response  $response
     * @param int|int[] $statusCode
     * @param string    $entityName
     * @param string    $requestType
     */
    protected static function assertApiResponseStatusCodeEquals(
        Response $response,
        $statusCode,
        $entityName,
        $requestType
    ) {
        try {
            static::assertResponseStatusCodeEquals($response, $statusCode);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $e = new \PHPUnit\Framework\ExpectationFailedException(
                sprintf(
                    'Expects %s status code for "%s" request for entity: "%s". Error message: %s',
                    is_array($statusCode) ? implode(', ', $statusCode) : $statusCode,
                    $requestType,
                    $entityName,
                    $e->getMessage()
                ),
                $e->getComparisonFailure()
            );
            throw $e;
        }
    }

    /**
     * @param Response   $response
     * @param int|int[]  $statusCode
     * @param string     $entityName
     * @param string     $requestType
     * @param array|null $content
     */
    protected static function assertUpdateApiResponseStatusCodeEquals(
        Response $response,
        $statusCode,
        $entityName,
        $requestType,
        $content
    ) {
        try {
            static::assertResponseStatusCodeEquals($response, $statusCode);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $e = new \PHPUnit\Framework\ExpectationFailedException(
                sprintf(
                    'Expects %s status code for "%s" request for entity: "%s". Error message: %s. Content: %s',
                    is_array($statusCode) ? implode(', ', $statusCode) : $statusCode,
                    $requestType,
                    $entityName,
                    $e->getMessage(),
                    is_array($content) ? json_encode($content) : (string)$content
                ),
                $e->getComparisonFailure()
            );
            throw $e;
        }
    }

    /**
     * Asserts response status code equals.
     *
     * @param Response    $response
     * @param int|int[]   $statusCode
     * @param string|null $message
     */
    protected static function assertResponseStatusCodeEquals(Response $response, $statusCode, $message = null)
    {
        try {
            if (is_array($statusCode)) {
                if (!in_array($response->getStatusCode(), $statusCode, true)) {
                    $failureMessage = sprintf(
                        'Failed asserting that %s is one of %s',
                        $response->getStatusCode(),
                        implode(', ', $statusCode)
                    );
                    if (!empty($message)) {
                        $failureMessage = $message . "\n" . $failureMessage;
                    }
                    throw new \PHPUnit\Framework\ExpectationFailedException($failureMessage);
                }
            } else {
                \PHPUnit\Framework\TestCase::assertEquals($statusCode, $response->getStatusCode(), $message);
            }
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            if ($response->getStatusCode() >= Response::HTTP_BAD_REQUEST
                && static::isApplicableContentType($response->headers)
            ) {
                $e = new \PHPUnit\Framework\ExpectationFailedException(
                    $e->getMessage() . "\nResponse content: " . $response->getContent(),
                    $e->getComparisonFailure()
                );
            }
            throw $e;
        }
    }

    /**
     * Asserts the given response header equals to the expected value.
     *
     * @param Response $response
     * @param string   $headerName
     * @param mixed    $expectedValue
     */
    protected static function assertResponseHeader(Response $response, string $headerName, string $expectedValue)
    {
        self::assertEquals(
            $expectedValue,
            $response->headers->get($headerName),
            sprintf('"%s" response header', $headerName)
        );
    }

    /**
     * Asserts the given response header equals to the expected value.
     *
     * @param Response $response
     * @param string   $headerName
     */
    protected static function assertResponseHeaderNotExists(Response $response, string $headerName)
    {
        self::assertFalse(
            $response->headers->has($headerName),
            sprintf('"%s" header should not exist in the response', $headerName)
        );
    }

    /**
     * Asserts "Allow" response header equals to the expected value.
     *
     * @param Response $response
     * @param string   $expectedAllowedMethods
     * @param string   $message
     */
    protected static function assertAllowResponseHeader(
        Response $response,
        string $expectedAllowedMethods,
        string $message = ''
    ) {
        self::assertEquals($expectedAllowedMethods, $response->headers->get('Allow'), $message);
    }

    /**
     * Asserts response status code equals to 405 (Method Not Allowed)
     * and "Allow" response header equals to the expected value.
     *
     * @param Response $response
     * @param string   $expectedAllowedMethods
     * @param string   $message
     */
    protected static function assertMethodNotAllowedResponse(
        Response $response,
        string $expectedAllowedMethods,
        string $message = ''
    ) {
        self::assertResponseStatusCodeEquals($response, Response::HTTP_METHOD_NOT_ALLOWED, $message);
        self::assertAllowResponseHeader($response, $expectedAllowedMethods, $message);
    }

    /**
     * Asserts an array contains the expected array.
     *
     * @param array  $expected
     * @param mixed  $actual
     * @param string $message
     */
    protected static function assertArrayContains(array $expected, $actual, string $message = '')
    {
        self::assertThat($actual, new ArrayContainsConstraint($expected, false), $message);
    }

    /**
     * @param ResponseHeaderBag $headers
     *
     * @return bool
     */
    protected static function isApplicableContentType(ResponseHeaderBag $headers)
    {
        return $headers->contains('Content-Type', 'application/json');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return self::getContainer()->get('doctrine')->getManager();
    }

    /**
     * Clears the default entity manager.
     */
    protected function clearEntityManager()
    {
        try {
            $this->getEntityManager()->clear();
        } catch (DBALException $e) {
            /**
             * Suppress database related exceptions during the clearing of the entity manager,
             * if it was requested for safe handling of "kernel.terminate" event.
             * This is needed to prevent unexpected exceptions
             * if some database related exception occurs during handling of API request
             * (e.g. Doctrine\DBAL\Exception\UniqueConstraintViolationException).
             * As functional tests work inside a database transaction, any query to the database
             * after such exception can raise "current transaction is aborted,
             * commands ignored until end of transaction block" SQL exception
             * if PostgreSQL is used in the tests.
             */
            if ($this->isKernelTerminateHandlerDisabled) {
                throw $e;
            }
        }
    }

    /**
     * @return TestConfigRegistry
     */
    protected function getConfigRegistry()
    {
        return self::getContainer()->get('oro_api.tests.config_registry');
    }

    /**
     * Appends a configuration of an API resource.
     * This method may be helpful if you create some general functionality
     * and need to test it for different configurations without creating a test entity
     * for each configuration.
     * Please note that the configuration is restored after each test and you do not need to do it manually.
     *
     * @param string $entityClass          The class name of API resource
     * @param array  $config               The config to append,
     *                                     e.g. ['fields' => ['renamedField' => ['property_path' => 'field']]]
     * @param bool   $affectResourcesCache Whether the appended config affects the API resources or sub-resources
     *                                     cache. E.g. this can happen when an association is renamed or excluded,
     *                                     or when a API resource is added or excluded
     */
    protected function appendEntityConfig($entityClass, array $config, $affectResourcesCache = false)
    {
        $this->getConfigRegistry()->appendEntityConfig(
            $this->getRequestType(),
            $entityClass,
            $config,
            $affectResourcesCache
        );
        // disable the kernel reboot to avoid loosing of changes in configs
        if (null !== $this->client && !$this->isKernelRebootDisabled) {
            $this->client->disableReboot();
            $this->isKernelRebootDisabled = true;
        }
    }

    /**
     * Restored default configuration of API resources.
     *
     * @after
     */
    protected function restoreConfigs()
    {
        $this->getConfigRegistry()->restoreConfigs($this->getRequestType());
        // restore the kernel reboot if it was disabled in appendEntityConfig method
        if ($this->isKernelRebootDisabled) {
            $this->isKernelRebootDisabled = false;
            if (null !== $this->client) {
                $this->client->enableReboot();
            }
        }
    }

    /**
     * Removes all messages from the request type logger that is used for test purposes.
     *
     * @after
     */
    protected function clearRequestTypeLogger()
    {
        $logger = $this->getRequestTypeLogger();
        if (null !== $logger) {
            $logger->cleanLogs();
        }
    }

    /**
     * @return BufferingLogger|null
     */
    protected function getRequestTypeLogger()
    {
        return null !== $this->client
            ? $this->client->getContainer()->get('oro_api.tests.request_type_logger')
            : null;
    }

    /**
     * @return string[]
     */
    protected function getRequestTypeLogMessages()
    {
        $logger = $this->getRequestTypeLogger();
        if (null === $logger) {
            return [];
        }

        $messages = [];
        $logs = $logger->cleanLogs();
        foreach ($logs as $entry) {
            $messages[] = $entry[1];
        }

        return $messages;
    }
}
