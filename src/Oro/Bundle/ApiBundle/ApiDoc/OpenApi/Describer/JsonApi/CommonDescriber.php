<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\JsonApi;

use OpenApi\Annotations as OA;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\DescriberInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ModelNameUtil;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ModelStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ModelStorageAwareTrait;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\SchemaStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\SchemaStorageAwareTrait;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\IdentifierDescriptionHelper;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

/**
 * Describes common JSON:API objects, like "meta", "link", "error", "failure", etc.
 */
class CommonDescriber implements DescriberInterface, SchemaStorageAwareInterface, ModelStorageAwareInterface
{
    use SchemaStorageAwareTrait;
    use ModelStorageAwareTrait;

    public const META = 'meta';
    public const LINK = 'link';
    public const LINKS = 'links';

    private const ERROR = 'error';
    private const ERRORS = 'errors';
    private const FAILURE = 'failure';

    /**
     * {@inheritDoc}
     */
    public function describe(OA\OpenApi $api, array $options): void
    {
        $this->registerMetaObject($api);
        $this->registerLinkObject($api);
        $this->registerLinksObject($api);
        $this->registerErrorObject($api);
        $this->registerErrorsObject($api);
        $this->registerFailureObject($api);
        $this->registerRelationshipModels($api);
    }

    private function registerMetaObject(OA\OpenApi $api): void
    {
        $schema = $this->schemaStorage->findSchema($api, self::META);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, self::META);
            $schema->type = Util::TYPE_OBJECT;
            $schema->additionalProperties = true;
        }
    }

    private function registerLinkObject(OA\OpenApi $api): void
    {
        $schema = $this->schemaStorage->findSchema($api, self::LINK);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, self::LINK);
            $schema->type = Util::TYPE_OBJECT;
            $uriSchema = Util::createType($schema, Util::TYPE_STRING);
            $uriSchema->format = 'uri-reference';
            $uriSchema->description = 'A string containing the link’s URL';
            $uriObjSchema = Util::createType($schema, Util::TYPE_OBJECT);
            $uriObjSchema->properties = [
                Util::createStringProperty(
                    $uriSchema,
                    'href',
                    'A string containing the link’s URL.',
                    'uri-reference'
                ),
                Util::createRefProperty($uriSchema, JsonApiDoc::META, self::META)
            ];
            $uriObjSchema->required = ['href'];
            $schema->oneOf = [$uriSchema, $uriObjSchema];
        }
    }

    private function registerLinksObject(OA\OpenApi $api): void
    {
        $schema = $this->schemaStorage->findSchema($api, JsonApiDoc::LINKS);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, JsonApiDoc::LINKS);
            $schema->type = Util::TYPE_OBJECT;
            $schema->additionalProperties = Util::createAdditionalProperties($schema, self::LINK);
        }
    }

    private function registerErrorObject(OA\OpenApi $api): void
    {
        $schema = $this->schemaStorage->findSchema($api, self::ERROR);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, self::ERROR);
            $schema->type = Util::TYPE_OBJECT;

            $sourceProperty = Util::createChildItem(OA\Property::class, $schema);
            $sourceProperty->type = Util::TYPE_OBJECT;
            $sourceProperty->property = 'source';
            $sourceProperty->description = 'An object containing references to the source of the error.';
            $sourceProperty->properties = [
                Util::createStringProperty(
                    $sourceProperty,
                    'pointer',
                    'A [JSON Pointer](https://tools.ietf.org/html/rfc6901) to the associated entity'
                    . ' in the request document (e.g. `/data` for a primary data object, or `/data/attributes/title`'
                    . ' for a specific attribute).'
                ),
                Util::createStringProperty(
                    $sourceProperty,
                    'parameter',
                    'A string indicating which URI query parameter caused the error.'
                )
            ];

            $schema->properties = [
                Util::createStringProperty(
                    $schema,
                    'id',
                    'A unique identifier for this particular occurrence of the problem.'
                ),
                Util::createStringProperty(
                    $schema,
                    'status',
                    'The HTTP status code applicable to this problem.'
                ),
                Util::createStringProperty(
                    $schema,
                    'code',
                    'An application-specific error code.'
                ),
                Util::createStringProperty(
                    $schema,
                    'title',
                    'A short, human-readable summary of the problem'
                    . ' that is not changed from occurrence to occurrence of the problem.'
                ),
                Util::createStringProperty(
                    $schema,
                    'detail',
                    'A human-readable explanation specific to this occurrence of the problem.'
                ),
                $sourceProperty,
                Util::createRefProperty($schema, JsonApiDoc::LINKS, self::LINKS),
                Util::createRefProperty($schema, JsonApiDoc::META, self::META)
            ];
        }
    }

    private function registerErrorsObject(OA\OpenApi $api): void
    {
        $schema = $this->schemaStorage->findSchema($api, self::ERRORS);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, self::ERRORS);
            $schema->type = Util::TYPE_ARRAY;
            $schema->items = Util::createArrayItems($schema, self::ERROR);
            $schema->uniqueItems = true;
        }
    }

    private function registerFailureObject(OA\OpenApi $api): void
    {
        $schema = $this->schemaStorage->findSchema($api, self::FAILURE);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, self::FAILURE);
            $schema->type = Util::TYPE_OBJECT;
            $schema->properties = [
                Util::createRefProperty($schema, JsonApiDoc::ERRORS, self::ERRORS),
                Util::createRefProperty($schema, JsonApiDoc::LINKS, self::LINKS),
                Util::createRefProperty($schema, JsonApiDoc::META, self::META)
            ];
            $schema->required = [JsonApiDoc::ERRORS];
        }
    }

    /**
     * Registers reusable models for to-one and to-many relationship resources without a concrete entity type,
     * e.g. "/api/entity/{id}/relationships/activityTargets".
     */
    private function registerRelationshipModels(OA\OpenApi $api): void
    {
        $modelNamePrefix = 'relationship';
        $model = [
            JsonApiDoc::ID => [
                'description' => IdentifierDescriptionHelper::ID_DESCRIPTION,
                'required'    => true,
                'dataType'    => Util::TYPE_STRING,
                'actualType'  => Util::TYPE_STRING
            ]
        ];
        // to-one model
        $this->modelStorage->registerModel(
            $api,
            $model,
            ModelNameUtil::buildModelName($modelNamePrefix),
            null,
            false,
            true,
            true
        );
        // to-many model
        $this->modelStorage->registerModel(
            $api,
            $model,
            ModelNameUtil::buildModelName($modelNamePrefix, null, true),
            null,
            true,
            true,
            true
        );
    }
}
