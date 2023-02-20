<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\TestConfigRegistry;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Assert\ArrayContainsConstraint;
use Oro\Component\Testing\Logger\BufferingLogger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Yaml\Yaml;

/**
 * The base class for API functional tests.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class ApiTestCase extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private bool $isKernelRebootDisabled = false;

    abstract protected function getRequestType(): RequestType;

    protected function getEntityType(string $entityClass): string
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->getValueNormalizer(),
            $entityClass,
            $this->getRequestType()
        );
    }

    protected function getEntityClass(string $entityType): string
    {
        return ValueNormalizerUtil::convertToEntityClass(
            $this->getValueNormalizer(),
            $entityType,
            $this->getRequestType()
        );
    }

    protected function getDoctrineHelper(): DoctrineHelper
    {
        return self::getContainer()->get('oro_api.doctrine_helper');
    }

    protected function getValueNormalizer(): ValueNormalizer
    {
        return self::getContainer()->get('oro_api.value_normalizer');
    }

    protected function isActionEnabled(string $entityClass, string $action): bool
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
    protected function getEntities(): array
    {
        $result = [];
        $doctrineHelper = $this->getDoctrineHelper();
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

    protected function runForEntities(callable $callback): void
    {
        $entities = $this->getEntities();
        foreach ($entities as [$entityClass, $excludedActions]) {
            try {
                $callback($entityClass, $excludedActions);
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    sprintf('The test failed for the "%s" entity.', $entityClass),
                    $e->getCode(),
                    $e
                );
            }
        }
    }

    /**
     * @return array [[entity class, association name, ApiSubresource], ...]
     */
    protected function getSubresources(): array
    {
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

    protected function runForSubresources(callable $callback): void
    {
        $subresources = $this->getSubresources();
        foreach ($subresources as [$entityClass, $associationName, $subresource]) {
            try {
                $callback($entityClass, $associationName, $subresource);
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    sprintf(
                        'The test failed for the "%s" association of the "%s" entity.',
                        $associationName,
                        $entityClass
                    ),
                    $e->getCode(),
                    $e
                );
            }
        }
    }

    protected function findEntityId(string $entityClass): mixed
    {
        /** @var EntityManagerInterface|null $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass($entityClass);
        if (null === $em) {
            return null;
        }

        $qb = $em->getRepository($entityClass)->createQueryBuilder('e')
            ->select(implode(',', array_map(
                function ($fieldName) {
                    return 'e.' . $fieldName;
                },
                $em->getClassMetadata($entityClass)->getIdentifierFieldNames()
            )))
            ->setMaxResults(1);
        $ids = self::getContainer()->get('oro_security.acl_helper')->apply($qb)->getArrayResult();
        if (empty($ids)) {
            return null;
        }

        $ids = reset($ids);

        return 1 === count($ids)
            ? reset($ids)
            : $ids;
    }

    protected function getRequestDataFolderName(): string
    {
        return 'requests';
    }

    protected function getResponseDataFolderName(): string
    {
        return 'responses';
    }

    /**
     * Loads the response content and convert it to an array.
     */
    protected function loadYamlData(string $fileName, string $folderName = null): array
    {
        return Yaml::parse($this->loadData($fileName, $folderName));
    }

    /**
     * Loads the response content.
     */
    protected function loadData(string $fileName, string $folderName = null): string
    {
        if ($this->isRelativePath($fileName)) {
            $fileName = $this->getTestResourcePath($folderName, $fileName);
        }
        $file = self::getContainer()->get('file_locator')->locate($fileName);
        self::assertTrue(is_file($file), sprintf('File "%s" with expected content not found', $fileName));

        return file_get_contents($file);
    }

    /**
     * Converts the given request to an array that can be sent to the server.
     *
     * @param array|string $request The file name or full file path to YAML template file or array
     *
     * @return array
     */
    protected function getRequestData(array|string $request): array
    {
        if (is_string($request) && $this->isRelativePath($request)) {
            $request = $this->getTestResourcePath($this->getRequestDataFolderName(), $request);
        }

        return self::processTemplateData($request);
    }

    /**
     * Converts the given response to an array that can be used to compare it
     * with a response received from the server.
     *
     * @param array|string $expectedContent The file name or full file path to YAML template file or array
     *
     * @return array
     */
    protected function getResponseData(array|string $expectedContent): array
    {
        if (is_string($expectedContent)) {
            $expectedContent = $this->loadYamlData($expectedContent, $this->getResponseDataFolderName());
        }

        return self::processTemplateData($expectedContent);
    }

    /**
     * Resolves "{baseUrl}" placeholders and all entity references in the given expected content.
     */
    protected function getExpectedContentWithPaginationLinks(array $expectedContent): array
    {
        $content = Yaml::dump($expectedContent);
        $content = str_replace('{baseUrl}', $this->getApiBaseUrl(), $content);

        return self::processTemplateData(Yaml::parse($content));
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
        array|string $expectedContent,
        Response $response,
        string $key = 'id',
        string $placeholder = 'new'
    ): array {
        $expectedContent = $this->getResponseData($expectedContent);
        $content = self::jsonToArray($response->getContent());
        $this->walkResponseContent($expectedContent, $content, $key, $placeholder);

        return $expectedContent;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
     * Asserts response status code for API request equals to any of the given status code.
     */
    protected static function assertApiResponseStatusCodeEquals(
        Response $response,
        int|array $statusCode,
        string $entityName,
        string $requestType
    ): void {
        try {
            static::assertResponseStatusCodeEqualsToOneOf($response, $statusCode);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                sprintf(
                    'Expects %s status code for "%s" request for entity: "%s". Error message: %s',
                    is_array($statusCode) ? implode(', ', $statusCode) : $statusCode,
                    $requestType,
                    $entityName,
                    $e->getMessage()
                ),
                $e->getComparisonFailure()
            );
        }
    }

    /**
     * Asserts response status code for update API request equals to any of the given status code.
     */
    protected static function assertUpdateApiResponseStatusCodeEquals(
        Response $response,
        int|array $statusCode,
        string $entityName,
        string $requestType,
        ?array $content
    ): void {
        try {
            static::assertResponseStatusCodeEqualsToOneOf($response, $statusCode);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                sprintf(
                    'Expects %s status code for "%s" request for entity: "%s". Error message: %s. Content: %s',
                    is_array($statusCode) ? implode(', ', $statusCode) : $statusCode,
                    $requestType,
                    $entityName,
                    $e->getMessage(),
                    is_array($content) ? json_encode($content, JSON_THROW_ON_ERROR) : (string)$content
                ),
                $e->getComparisonFailure()
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    protected static function assertResponseStatusCodeEquals(
        Response $response,
        int $statusCode,
        string $message = ''
    ): void {
        try {
            \PHPUnit\Framework\TestCase::assertEquals($statusCode, $response->getStatusCode(), $message);
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
     * Asserts response status code equals to one of the given status code.
     */
    private static function assertResponseStatusCodeEqualsToOneOf(
        Response $response,
        int|array $statusCode,
        string $message = ''
    ): void {
        try {
            if (!is_array($statusCode)) {
                \PHPUnit\Framework\TestCase::assertEquals($statusCode, $response->getStatusCode(), $message);
            } elseif (!in_array($response->getStatusCode(), $statusCode, true)) {
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
     * Asserts response status code does not equal to any of the given status code.
     */
    protected static function assertResponseStatusCodeNotEquals(
        Response $response,
        int|array $statusCode,
        string $message = ''
    ): void {
        try {
            if (!is_array($statusCode)) {
                \PHPUnit\Framework\TestCase::assertNotEquals($statusCode, $response->getStatusCode(), $message);
            } elseif (in_array($response->getStatusCode(), $statusCode, true)) {
                $failureMessage = sprintf(
                    'Failed asserting that %s is not one of %s',
                    $response->getStatusCode(),
                    implode(', ', $statusCode)
                );
                if (!empty($message)) {
                    $failureMessage = $message . "\n" . $failureMessage;
                }
                throw new \PHPUnit\Framework\ExpectationFailedException($failureMessage);
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
     * Asserts response status code equals to 405 (Method Not Allowed)
     * and "Allow" response header equals to the expected value.
     */
    protected static function assertMethodNotAllowedResponse(
        Response $response,
        string $expectedAllowedMethods,
        string $message = ''
    ): void {
        self::assertResponseStatusCodeEquals($response, Response::HTTP_METHOD_NOT_ALLOWED, $message);
        self::assertAllowResponseHeader($response, $expectedAllowedMethods, $message);
    }

    /**
     * Asserts an array contains the expected array.
     */
    protected static function assertArrayContains(array $expected, mixed $actual, string $message = ''): void
    {
        self::assertThat($actual, new ArrayContainsConstraint($expected, false), $message);
    }

    protected static function isApplicableContentType(ResponseHeaderBag $headers): bool
    {
        return $headers->contains('Content-Type', 'application/json');
    }

    protected function getEntityManager(string $entityClass = null): EntityManagerInterface
    {
        $doctrine = self::getContainer()->get('doctrine');
        if ($entityClass) {
            return $doctrine->getManagerForClass($entityClass);
        }

        return $doctrine->getManager();
    }

    /**
     * Clears the default entity manager.
     */
    protected function clearEntityManager(): void
    {
        $em = $this->getEntityManager();
        if ($em->isOpen()) {
            $em->clear();
        }
    }

    protected function getConfigRegistry(): TestConfigRegistry
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
    protected function appendEntityConfig(string $entityClass, array $config, bool $affectResourcesCache = false): void
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
    protected function restoreConfigs(): void
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
    protected function clearRequestTypeLogger(): void
    {
        $logger = $this->getRequestTypeLogger();
        if (null !== $logger) {
            $logger->cleanLogs();
        }
    }

    protected function getRequestTypeLogger(): ?BufferingLogger
    {
        return $this->client?->getContainer()->get('oro_api.tests.request_type_logger');
    }

    /**
     * @return string[]
     */
    protected function getRequestTypeLogMessages(): array
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

    /**
     * Removes all messages from the customize form data logger that is used for test purposes.
     *
     * @after
     */
    protected function clearCustomizeFormDataLogger(): void
    {
        $logger = $this->getCustomizeFormDataLogger();
        if (null !== $logger) {
            $logger->cleanLogs();
        }
    }

    protected function getCustomizeFormDataLogger(): ?BufferingLogger
    {
        return $this->client?->getContainer()->get('oro_api.tests.customize_form_data_logger');
    }

    protected function checkTwigState(): void
    {
        $this->enableTwig(null);
    }

    /**
     * @see \Oro\Bundle\ApiBundle\Tests\Functional\Environment\TestTwigExtension
     */
    protected function enableTwig(?bool $enable = true): void
    {
        self::getContainer()->get('oro_api.tests.twig_state')->enableTwig($enable);
    }
}
