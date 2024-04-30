<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\JsonApi;

use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\ModelNormalizerInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

/**
 * Provides model normalization logic applicable to JSON:API.
 */
class ModelNormalizer implements ModelNormalizerInterface
{
    private ModelNormalizerInterface $innerModelNormalizer;

    public function __construct(ModelNormalizerInterface $innerModelNormalizer)
    {
        $this->innerModelNormalizer = $innerModelNormalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeModel(array $model, string $action, bool $isResponseModel): array
    {
        $model = $this->innerModelNormalizer->normalizeModel($model, $action, $isResponseModel);
        if (isset($model[JsonApiDoc::ID])) {
            if (!$isResponseModel
                && !($model[JsonApiDoc::ID]['required'] ?? false)
                && $this->isActionWithRequiredIdInRequest($action)
            ) {
                $model[JsonApiDoc::ID]['required'] = true;
            }
            if (Util::TYPE_STRING !== $model[JsonApiDoc::ID]['actualType']) {
                $model[JsonApiDoc::ID]['actualType'] = Util::TYPE_STRING;
                $model[JsonApiDoc::ID]['dataType'] = Util::TYPE_STRING;
            }
        }

        return $model;
    }

    private function isActionWithRequiredIdInRequest(string $action): bool
    {
        return
            ApiAction::UPDATE === $action
            || ApiAction::UPDATE_RELATIONSHIP === $action
            || ApiAction::UPDATE_SUBRESOURCE === $action;
    }
}
