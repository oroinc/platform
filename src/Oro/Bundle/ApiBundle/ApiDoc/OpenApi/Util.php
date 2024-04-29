<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi;

use OpenApi\Annotations as OA;
use OpenApi\Context;
use OpenApi\Generator;

/**
 * Provides a set of static methods that help building OpenAPI specification.
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class Util
{
    public const OPERATIONS = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace'];

    public const TYPE_OBJECT = 'object';
    public const TYPE_ARRAY = 'array';
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_NUMBER = 'number';
    public const TYPE_BOOLEAN = 'boolean';

    public static function createContext(array $properties, ?Context $parent): Context
    {
        return new Context($properties, $parent);
    }

    public static function createChildContext(OA\AbstractAnnotation $parent): Context
    {
        return self::createContext(['nested' => $parent], $parent->_context);
    }

    /**
     * @template T
     * @psalm-param class-string<T> $class
     * @psalm-return T
     */
    public static function createItem(string $class, Context $context): OA\AbstractAnnotation
    {
        return new $class(['_context' => $context]);
    }

    /**
     * @template T
     * @psalm-param class-string<T> $class
     * @psalm-return T
     */
    public static function createChildItem(
        string $class,
        OA\AbstractAnnotation $parent,
        ?string $path = null
    ): OA\AbstractAnnotation {
        $item = self::createItem($class, self::createChildContext($parent));
        if ($path) {
            $item->_context->filename = self::buildPath($path, $parent->_context->filename);
        }

        return $item;
    }

    public static function createSchemaRef(OA\AbstractAnnotation $parent, string $schema): OA\Schema
    {
        $schemaObj = self::createChildItem(OA\Schema::class, $parent);
        $schemaObj->ref = self::getSchemaRefPath($schema);

        return $schemaObj;
    }

    public static function getSchemaRefPath(string $schema): string
    {
        return OA\Components::SCHEMA_REF . $schema;
    }

    public static function createParameterRef(OA\AbstractAnnotation $parent, string $parameter): OA\Parameter
    {
        $parameterObj = self::createChildItem(OA\Parameter::class, $parent);
        $parameterObj->ref = OA\Components::COMPONENTS_PREFIX . 'parameters/' . $parameter;

        return $parameterObj;
    }

    public static function createHeaderRef(OA\AbstractAnnotation $parent, string $name, string $refName): OA\Header
    {
        $headerObj = self::createChildItem(OA\Header::class, $parent);
        $headerObj->header = $name;
        $headerObj->ref = OA\Components::COMPONENTS_PREFIX . 'headers/' . $refName;

        return $headerObj;
    }

    public static function createRequestBodyRef(OA\AbstractAnnotation $parent, string $requestBody): OA\RequestBody
    {
        $parameterObj = self::createChildItem(OA\RequestBody::class, $parent);
        $parameterObj->ref = OA\Components::COMPONENTS_PREFIX . 'requestBodies/' . $requestBody;

        return $parameterObj;
    }

    public static function createResponseRef(
        OA\AbstractAnnotation $parent,
        int $statusCode,
        string $response
    ): OA\Response {
        $responseObj = self::createChildItem(OA\Response::class, $parent);
        $responseObj->response = $statusCode;
        $responseObj->ref = OA\Components::COMPONENTS_PREFIX . 'responses/' . $response;

        return $responseObj;
    }

    public static function createArrayItems(OA\Schema $parent, string $schema, bool $isSimpleType = false): OA\Items
    {
        $result = self::createChildItem(OA\Items::class, $parent);
        if ($isSimpleType) {
            $result->type = $schema;
        } else {
            $result->ref = self::getSchemaRefPath($schema);
        }

        return $result;
    }

    public static function createType(OA\AbstractAnnotation $parent, string $type): OA\Schema
    {
        $schema = self::createChildItem(OA\Schema::class, $parent);
        $schema->type = $type;

        return $schema;
    }

    public static function createStringProperty(
        OA\AbstractAnnotation $parent,
        string $name,
        ?string $description = null,
        ?string $format = null
    ): OA\Property {
        $prop = self::createChildItem(OA\Property::class, $parent);
        $prop->property = $name;
        $prop->type = self::TYPE_STRING;
        if ($description) {
            $prop->description = $description;
        }
        if (null !== $format) {
            $prop->format = $format;
        }

        return $prop;
    }

    public static function createRefProperty(
        OA\AbstractAnnotation $parent,
        string $name,
        string $refName,
        ?string $description = null
    ): OA\Property {
        $prop = self::createChildItem(OA\Property::class, $parent);
        $prop->property = $name;
        $prop->ref = self::getSchemaRefPath($refName);
        if ($description) {
            $prop->description = $description;
        }

        return $prop;
    }

    public static function createAdditionalProperties(
        OA\AbstractAnnotation $parent,
        string $schema
    ): OA\AdditionalProperties {
        $additionalProperties = self::createChildItem(OA\AdditionalProperties::class, $parent);
        $additionalProperties->ref = self::getSchemaRefPath($schema);

        return $additionalProperties;
    }

    public static function ensureComponentCollectionInitialized(OA\OpenApi $api, string $collectionName): void
    {
        if (!$api->components instanceof OA\Components) {
            $api->components = self::createItem(OA\Components::class, self::createChildContext($api));
        }
        if (Generator::isDefault($api->components->{$collectionName})) {
            $api->components->{$collectionName} = [];
        }
    }

    private static function buildPath(string $path, ?string $parentPath): string
    {
        if (!$parentPath) {
            return '"' . $path . '"';
        }

        return rtrim($parentPath, '"') . ' > ' . $path . '"';
    }
}
