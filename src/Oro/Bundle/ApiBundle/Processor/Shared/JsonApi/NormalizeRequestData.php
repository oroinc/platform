<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\ApiBundle\Processor\SingleItemUpdateContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Converts JSON API data to plain array.
 */
class NormalizeRequestData implements ProcessorInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemUpdateContext $context */

        $requestData = $context->getRequestData();

        if (!array_key_exists(JsonApiDoc::DATA, $requestData)) {
            // a request data is already normalized
            return;
        }

        $relations = [];
        if (array_key_exists(JsonApiDoc::RELATIONSHIPS, $requestData[JsonApiDoc::DATA])) {
            $requestType = $context->getRequestType();
            foreach ($requestData[JsonApiDoc::DATA][JsonApiDoc::RELATIONSHIPS] as $relationName => $data) {
                $data = $data[JsonApiDoc::DATA];

                // Relation data can be null in case -to-one and an empty array in case -to-many relation.
                // In this case we should process this relation data as empty relation
                if ($data === null || empty ($data)) {
                    $relations[$relationName] = [];
                    continue;
                }

                if (array_keys($data) !== range(0, count($data) - 1)) {
                    $relations[$relationName] = $this->normalizeItemData($data, $requestType);
                } else {
                    foreach ($data as $collectionItem) {
                        $relations[$relationName][]= $this->normalizeItemData($collectionItem, $requestType);
                    }
                }
            }
        }

        $context->setRequestData(
            array_merge(
                $requestData[JsonApiDoc::DATA][JsonApiDoc::ATTRIBUTES],
                $relations
            )
        );
    }

    /**
     * @param array       $collectionItem ['type' => type, 'id' => 'id_value']
     * @param RequestType $requestType
     *
     * @return array ['class' => class name, 'id' => 'id_value']
     */
    protected function normalizeItemData(array $collectionItem, RequestType $requestType)
    {
        $entityClass = ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $collectionItem[JsonApiDoc::TYPE],
            $requestType,
            true
        );

        return [
            'class' => $entityClass,
            'id' => $collectionItem[JsonApiDoc::ID]
        ];
    }
}
