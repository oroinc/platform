<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\JsonApi;

use OpenApi\Annotations as OA;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\DataTypeDescribeHelper;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\ModelDescriberInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\ResourceInfoProviderInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ModelNameUtil;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ModelStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ModelStorageAwareTrait;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\SchemaStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\SchemaStorageAwareTrait;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Describes JSON:API models.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ModelDescriber implements
    ModelDescriberInterface,
    ResetInterface,
    SchemaStorageAwareInterface,
    ModelStorageAwareInterface
{
    use SchemaStorageAwareTrait;
    use ModelStorageAwareTrait;

    private DataTypeDescribeHelper $dataTypeDescribeHelper;
    private ResourceInfoProviderInterface $resourceInfoProvider;
    private ?string $includedSectionModelName = null;

    public function __construct(
        DataTypeDescribeHelper $dataTypeDescribeHelper,
        ResourceInfoProviderInterface $resourceInfoProvider
    ) {
        $this->dataTypeDescribeHelper = $dataTypeDescribeHelper;
        $this->resourceInfoProvider = $resourceInfoProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->includedSectionModelName = null;
    }

    /**
     * {@inheritDoc}
     */
    public function describe(
        OA\OpenApi $api,
        OA\Schema $schema,
        array $model,
        string $modelName,
        ?string $entityType,
        bool $isCollection,
        bool $isPrimary,
        bool $isRelationship
    ): void {
        $schema->type = Util::TYPE_OBJECT;
        if ($entityType && $this->resourceInfoProvider->isResourceWithoutIdentifier($entityType)) {
            if ($isPrimary) {
                $this->describePrimaryModelWithoutId($api, $schema, $model, $modelName, $entityType, $isCollection);
            } elseif (!$isCollection) {
                $this->describeInnerModelWithoutId($api, $schema, $model, $modelName);
            }
        } elseif ($isPrimary) {
            $this->describePrimaryModel(
                $api,
                $schema,
                $model,
                $modelName,
                $entityType,
                $isCollection,
                $isRelationship
            );
        } elseif (!$isCollection) {
            $this->describeInnerModel($api, $schema, $model, $modelName, $entityType);
        }
    }

    public function describeUnion(OA\OpenApi $api, OA\Schema $schema, array $modelNames, bool $isCollection): void
    {
        $schema->type = Util::TYPE_OBJECT;
        $dataProperty = Util::createChildItem(OA\Property::class, $schema);
        $dataProperty->property = JsonApiDoc::DATA;
        if ($isCollection) {
            $dataProperty->type = Util::TYPE_ARRAY;
            $items = Util::createChildItem(OA\Items::class, $dataProperty);
            $items->oneOf = $this->getModelNamesRefs($dataProperty, $modelNames);
            $dataProperty->items = $items;
        } else {
            $dataProperty->type = Util::TYPE_OBJECT;
            $dataProperty->oneOf = $this->getModelNamesRefs($dataProperty, $modelNames);
        }

        $schema->properties = [
            $dataProperty,
            Util::createRefProperty($schema, JsonApiDoc::LINKS, CommonDescriber::LINKS),
            Util::createRefProperty($schema, JsonApiDoc::META, CommonDescriber::META)
        ];
        $schema->required = [JsonApiDoc::DATA];
    }

    private function describePrimaryModel(
        OA\OpenApi $api,
        OA\Schema $schema,
        array $model,
        string $modelName,
        ?string $entityType,
        bool $isCollection,
        bool $isRelationship
    ): void {
        $dataSchemaName = $this->modelStorage
            ->registerModel($api, $model, $this->buildInnerModelName($modelName, 'DataModel'), $entityType)
            ->schema;

        $dataProperty = Util::createChildItem(OA\Property::class, $schema);
        $dataProperty->property = JsonApiDoc::DATA;
        if ($isCollection) {
            $dataProperty->type = Util::TYPE_ARRAY;
            $dataProperty->items = Util::createArrayItems($schema, $dataSchemaName);
        } else {
            $dataProperty->type = Util::TYPE_OBJECT;
            $dataProperty->ref = Util::getSchemaRefPath($dataSchemaName);
        }

        $schema->properties = [
            $dataProperty,
            Util::createRefProperty(
                $schema,
                JsonApiDoc::LINKS,
                $isRelationship
                    ? $this->getRelationshipLinks($api, $isCollection)
                    : $this->getPrimaryModelLinks($api, $isCollection)
            ),
            Util::createRefProperty($schema, JsonApiDoc::META, CommonDescriber::META)
        ];
        if (!$isRelationship) {
            /** @noinspection UnsupportedStringOffsetOperationsInspection */
            $schema->properties[] = Util::createRefProperty(
                $schema,
                JsonApiDoc::INCLUDED,
                $this->getIncludedSectionModelName($api)
            );
        }
        $schema->required = [JsonApiDoc::DATA];
    }

    private function describePrimaryModelWithoutId(
        OA\OpenApi $api,
        OA\Schema $schema,
        array $model,
        string $modelName,
        ?string $entityType,
        bool $isCollection
    ): void {
        $metaSchemaName = $this->modelStorage
            ->registerModel($api, $model, $this->buildInnerModelName($modelName, 'MetaModel'), $entityType)
            ->schema;

        $metaProperty = Util::createChildItem(OA\Property::class, $schema);
        $metaProperty->property = JsonApiDoc::META;
        if ($isCollection) {
            $metaProperty->type = Util::TYPE_ARRAY;
            $metaProperty->items = Util::createArrayItems($schema, $metaSchemaName);
        } else {
            $metaProperty->type = Util::TYPE_OBJECT;
            $metaProperty->ref = Util::getSchemaRefPath($metaSchemaName);
        }

        $schema->properties = [
            $metaProperty,
            Util::createRefProperty($schema, JsonApiDoc::LINKS, CommonDescriber::LINKS)
        ];
        $schema->required = [JsonApiDoc::META];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function describeInnerModel(
        OA\OpenApi $api,
        OA\Schema $schema,
        array $model,
        string $modelName,
        ?string $entityType
    ): void {
        $properties = [
            $this->createTypeProperty($schema, $entityType ? [$entityType] : null)
        ];
        $requiredProperties = [JsonApiDoc::TYPE];
        $attributesProperty = Util::createChildItem(OA\Property::class, $schema);
        $attributes = [];
        $requiredAttributes = [];
        $relationshipsProperty = Util::createChildItem(OA\Property::class, $schema);
        $relationships = [];
        $requiredRelationships = [];
        foreach ($model as $name => $item) {
            $actualType = $item['actualType'];
            $required = ($item['required'] ?? false) && !($item['readonly'] ?? false);
            $description = $item['description'] ?? null;

            if ('model' === $actualType) {
                $relationships[] = Util::createRefProperty(
                    $relationshipsProperty,
                    $name,
                    $this->getToOneRelationship($api, $item['subType'] ?? null),
                    $description
                );
                if ($required) {
                    $requiredRelationships[] = $name;
                }
            } elseif ('collection' === $actualType) {
                $relationships[] = Util::createRefProperty(
                    $relationshipsProperty,
                    $name,
                    $this->getToManyRelationship($api, $item['subType'] ?? null),
                    $description
                );
                if ($required) {
                    $requiredRelationships[] = $name;
                }
            } elseif (JsonApiDoc::ID === $name) {
                $properties[] = $this->createProperty($api, $modelName, $schema, $name, $actualType, $description);
                if ($required) {
                    $requiredProperties[] = $name;
                }
            } else {
                $attributes[] = $this->createProperty(
                    $api,
                    $modelName,
                    $attributesProperty,
                    $name,
                    $actualType,
                    $description
                );
                if ($required) {
                    $requiredAttributes[] = $name;
                }
            }
        }

        if ($attributes || $relationships) {
            if ($attributes) {
                $attributesProperty->property = JsonApiDoc::ATTRIBUTES;
                $attributesProperty->type = Util::TYPE_OBJECT;
                $attributesProperty->properties = $attributes;
                if ($requiredAttributes) {
                    $attributesProperty->required = $requiredAttributes;
                }
                $properties[] = $attributesProperty;
            }
            if ($relationships) {
                $relationshipsProperty->property = JsonApiDoc::RELATIONSHIPS;
                $relationshipsProperty->type = Util::TYPE_OBJECT;
                $relationshipsProperty->properties = $relationships;
                if ($requiredRelationships) {
                    $relationshipsProperty->required = $requiredRelationships;
                }
                $properties[] = $relationshipsProperty;
            }
        }
        $properties[] = Util::createRefProperty($schema, JsonApiDoc::LINKS, $this->getInnerModelLinks($api));
        $properties[] = Util::createRefProperty($schema, JsonApiDoc::META, CommonDescriber::META);
        $schema->properties = $properties;
        $schema->required = $requiredProperties;
    }

    private function describeInnerModelWithoutId(
        OA\OpenApi $api,
        OA\Schema $schema,
        array $model,
        string $modelName
    ): void {
        $properties = [];
        $requiredProperties = [];
        foreach ($model as $name => $item) {
            $properties[] = $this->createProperty(
                $api,
                $modelName,
                $schema,
                $name,
                $item['actualType'],
                $item['description'] ?? null
            );
            if ($item['required'] ?? false) {
                $requiredProperties[] = $name;
            }
        }

        $schema->properties = $properties;
        if ($requiredProperties) {
            $schema->required = $requiredProperties;
        }
    }

    private function createTypeProperty(OA\AbstractAnnotation $parent, ?array $allowedTypes = null): OA\Property
    {
        $prop = Util::createStringProperty($parent, JsonApiDoc::TYPE, 'The type of an entity.');
        if ($allowedTypes) {
            $prop->enum = $allowedTypes;
        }

        return $prop;
    }

    private function createProperty(
        OA\OpenApi $api,
        string $modelName,
        OA\AbstractAnnotation $parent,
        string $name,
        string $dataType,
        ?string $description
    ): OA\Property {
        $prop = Util::createChildItem(OA\Property::class, $parent);
        $prop->property = $name;
        $this->dataTypeDescribeHelper->registerPropertyType($api, $modelName, $prop, $dataType);
        if ($description) {
            $prop->description = $description;
        }

        return $prop;
    }

    private function getToOneRelationship(OA\OpenApi $api, ?string $entityType): string
    {
        if ($entityType && $this->resourceInfoProvider->isUntypedEntityType($entityType)) {
            $entityType = null;
        }

        $schemaName = $entityType ? $entityType . 'RelationshipToOne' : 'relationshipToOne';
        $schema = $this->schemaStorage->findSchema($api, $schemaName);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, $schemaName);
            $schema->type = Util::TYPE_OBJECT;
            $schema->description = $entityType
                ? sprintf('A to-one relationship to "%s" resource.', $entityType)
                : 'A to-one relationship to a resource.';
            $dataProperty = Util::createChildItem(OA\Property::class, $schema);
            $dataProperty->property = JsonApiDoc::DATA;
            $dataProperty->type = Util::TYPE_OBJECT;
            $dataProperty->ref = Util::getSchemaRefPath($this->getLinkage($api, $entityType));
            $dataProperty->nullable = true;
            $schema->properties = [
                $dataProperty,
                Util::createRefProperty($schema, JsonApiDoc::LINKS, $this->getRelationshipLinks($api, false)),
                Util::createRefProperty($schema, JsonApiDoc::META, CommonDescriber::META)
            ];
            $schema->required = [JsonApiDoc::DATA];
        }

        return $schemaName;
    }

    private function getToManyRelationship(OA\OpenApi $api, ?string $entityType): string
    {
        if ($entityType && $this->resourceInfoProvider->isUntypedEntityType($entityType)) {
            $entityType = null;
        }

        $schemaName = $entityType ? $entityType . 'RelationshipToMany' : 'relationshipToMany';
        $schema = $this->schemaStorage->findSchema($api, $schemaName);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, $schemaName);
            $schema->type = Util::TYPE_OBJECT;
            $schema->description = $entityType
                ? sprintf('A to-many relationship to "%s" resource.', $entityType)
                : 'A to-many relationship to a resource.';
            $dataProperty = Util::createChildItem(OA\Property::class, $schema);
            $dataProperty->property = JsonApiDoc::DATA;
            $dataProperty->type = Util::TYPE_ARRAY;
            $dataProperty->items = Util::createArrayItems($schema, $this->getLinkage($api, $entityType));
            $dataProperty->uniqueItems = true;
            $schema->properties = [
                $dataProperty,
                Util::createRefProperty($schema, JsonApiDoc::LINKS, $this->getRelationshipLinks($api, true)),
                Util::createRefProperty($schema, JsonApiDoc::META, CommonDescriber::META)
            ];
            $schema->required = [JsonApiDoc::DATA];
        }

        return $schemaName;
    }

    private function getPrimaryModelLinks(OA\OpenApi $api, bool $isCollection): string
    {
        $schemaName = 'linksForTopLevel' . ($isCollection ? 'Collection' : 'Item');
        $schema = $this->schemaStorage->findSchema($api, $schemaName);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, $schemaName);
            $schema->type = Util::TYPE_OBJECT;
            $properties = [
                Util::createRefProperty(
                    $schema,
                    JsonApiDoc::LINK_SELF,
                    CommonDescriber::LINK,
                    'A link for the resource itself.'
                )
            ];
            if ($isCollection) {
                $properties[] = Util::createRefProperty(
                    $schema,
                    JsonApiDoc::LINK_FIRST,
                    CommonDescriber::LINK,
                    'A link for the first page of data.'
                );
                $properties[] = Util::createRefProperty(
                    $schema,
                    JsonApiDoc::LINK_LAST,
                    CommonDescriber::LINK,
                    'A link for the last page of data.'
                );
                $properties[] = Util::createRefProperty(
                    $schema,
                    JsonApiDoc::LINK_PREV,
                    CommonDescriber::LINK,
                    'A link for the previous page of data.'
                );
                $properties[] = Util::createRefProperty(
                    $schema,
                    JsonApiDoc::LINK_NEXT,
                    CommonDescriber::LINK,
                    'A link for the next page of data.'
                );
            }
            $schema->properties = $properties;
            $schema->additionalProperties = Util::createAdditionalProperties($schema, CommonDescriber::LINK);
        }

        return $schemaName;
    }

    private function getInnerModelLinks(OA\OpenApi $api): string
    {
        $schemaName = 'linksForDataModel';
        $schema = $this->schemaStorage->findSchema($api, $schemaName);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, $schemaName);
            $schema->type = Util::TYPE_OBJECT;
            $schema->properties = [
                Util::createRefProperty(
                    $schema,
                    JsonApiDoc::LINK_SELF,
                    CommonDescriber::LINK,
                    'A link for the resource itself.'
                )
            ];
            $schema->additionalProperties = Util::createAdditionalProperties($schema, CommonDescriber::LINK);
        }

        return $schemaName;
    }

    private function getRelationshipLinks(OA\OpenApi $api, bool $isCollection): string
    {
        $schemaName = 'linksForRelationshipTo' . ($isCollection ? 'Many' : 'One');
        $schema = $this->schemaStorage->findSchema($api, $schemaName);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, $schemaName);
            $schema->type = Util::TYPE_OBJECT;
            $schema->properties = [
                Util::createRefProperty(
                    $schema,
                    JsonApiDoc::LINK_SELF,
                    CommonDescriber::LINK,
                    'A link for the relationship itself.'
                ),
                Util::createRefProperty(
                    $schema,
                    JsonApiDoc::LINK_RELATED,
                    CommonDescriber::LINK,
                    $isCollection
                        ? 'A link provides access to resource objects linked in the relationship.'
                        : 'A link provides access to resource object linked in the relationship.'
                )
            ];
            $schema->additionalProperties = Util::createAdditionalProperties($schema, CommonDescriber::LINK);
        }

        return $schemaName;
    }

    private function getLinkage(OA\OpenApi $api, ?string $entityType): string
    {
        $schemaName = $entityType ? $entityType . 'Linkage' : 'linkage';
        $schema = $this->schemaStorage->findSchema($api, $schemaName);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, $schemaName);
            $schema->type = Util::TYPE_OBJECT;
            $schema->description = $entityType
                ? sprintf('A linkage to "%s" resource.', $entityType)
                : 'A linkage to a resource.';
            $schema->properties = [
                $this->createTypeProperty($schema, $entityType ? [$entityType] : null),
                Util::createStringProperty($schema, JsonApiDoc::ID),
                Util::createRefProperty($schema, JsonApiDoc::META, CommonDescriber::META)
            ];
            $schema->required = [JsonApiDoc::TYPE, JsonApiDoc::ID];
        }

        return $schemaName;
    }

    private function buildInnerModelName(string $modelName, string $suffix): string
    {
        return ModelNameUtil::getModelNameWithoutSuffix($modelName) . $suffix;
    }

    private function getModelNamesRefs(OA\AbstractAnnotation $parent, array $modelNames): array
    {
        $modelRefs = [];
        foreach ($modelNames as $modelName) {
            $modelRefs[] = Util::createSchemaRef($parent, $modelName);
        }

        return $modelRefs;
    }

    private function getIncludedSectionModelName(OA\OpenApi $api): string
    {
        if (null === $this->includedSectionModelName) {
            $this->includedSectionModelName = $this->dataTypeDescribeHelper
                ->registerType($api, Util::TYPE_OBJECT . '[]')
                ->schema;
        }

        return $this->includedSectionModelName;
    }
}
