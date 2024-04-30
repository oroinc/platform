<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

use OpenApi\Annotations as OA;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\ModelDescriberInterface;

/**
 * Represents a storage for OpenAPI models.
 */
class ModelStorage
{
    use ItemKeyBuildTrait;

    /** @var iterable<ModelDescriberInterface> */
    private iterable $modelDescribers;
    private SchemaStorage $schemaStorage;
    /** @var array [model key => model name, ...] */
    private array $modelNames = [];
    /** @var array [suggested model name => [model key => model name, ...], ...] */
    private array $modelKeys = [];

    public function __construct(iterable $modelDescribers, SchemaStorage $schemaStorage)
    {
        $this->modelDescribers = $modelDescribers;
        $this->schemaStorage = $schemaStorage;
    }

    public function registerModel(
        OA\OpenApi $api,
        array $model,
        string $suggestedModelName,
        ?string $entityType = null,
        bool $isCollection = false,
        bool $isPrimary = false,
        bool $isRelationship = false
    ): OA\Schema {
        $modelKey = $this->getModelKey($model, $entityType, $isCollection, $isPrimary);
        $existingModel = $this->findModel($api, $modelKey);
        if (null !== $existingModel) {
            return $existingModel;
        }

        $modelName = $this->resolveIModelName($suggestedModelName);
        $modelSchema = $this->schemaStorage->addSchema($api, $modelName);
        foreach ($this->modelDescribers as $modelDescriber) {
            $modelDescriber->describe(
                $api,
                $modelSchema,
                $model,
                $modelName,
                $entityType,
                $isCollection,
                $isPrimary,
                $isRelationship
            );
        }
        $this->saveModel($modelKey, $modelName, $suggestedModelName);

        return $modelSchema;
    }

    public function registerUnionModel(
        OA\OpenApi $api,
        array $modelNames,
        string $suggestedModelName,
        bool $isCollection = false
    ): OA\Schema {
        $modelKey = $this->getUnionModelKey($modelNames, $isCollection);
        $existingModel = $this->findModel($api, $modelKey);
        if (null !== $existingModel) {
            return $existingModel;
        }

        $modelName = $this->resolveIModelName($suggestedModelName);
        $modelSchema = $this->schemaStorage->addSchema($api, $modelName);
        foreach ($this->modelDescribers as $modelDescriber) {
            $modelDescriber->describeUnion($api, $modelSchema, $modelNames, $isCollection);
        }
        $this->saveModel($modelKey, $modelName, $suggestedModelName);

        return $modelSchema;
    }

    private function getModelKey(array $model, ?string $entityType, bool $isCollection, bool $isPrimary): string
    {
        $modelHash = $this->getItemKey($model);
        if ($entityType) {
            $modelHash .= '_' . $entityType;
        }
        if ($isCollection) {
            $modelHash .= '_c';
        }
        if (!$isPrimary) {
            $modelHash .= '_i';
        }

        return $modelHash;
    }

    private function getUnionModelKey(array $modelNames, bool $isCollection): string
    {
        $modelHash = $this->getItemKey($modelNames);
        if ($isCollection) {
            $modelHash .= '_c';
        }

        return $modelHash;
    }

    private function findModel(OA\OpenApi $api, string $modelKey): ?OA\Schema
    {
        $existingModelName = $this->modelNames[$modelKey] ?? null;
        if (null === $existingModelName) {
            return null;
        }

        return $this->schemaStorage->getSchema($api, $existingModelName);
    }

    private function resolveIModelName(string $suggestedModelName): string
    {
        $modelName = $suggestedModelName;
        // resolve the model name when there is another model with the requested suggested name
        $existingModelKeys = $this->modelKeys[$suggestedModelName] ?? null;
        if (null !== $existingModelKeys) {
            $baseModelName = ModelNameUtil::getModelNameWithoutSuffix($suggestedModelName);
            $modelName = $baseModelName . \count($existingModelKeys) . substr($modelName, \strlen($baseModelName));
        }

        return $modelName;
    }

    private function saveModel(string $modelKey, string $modelName, string $suggestedModelName): void
    {
        $this->modelNames[$modelKey] = $modelName;
        $this->modelKeys[$suggestedModelName][$modelKey] = $modelName;
    }
}
