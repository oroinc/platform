<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Prepares JSON.API request data to be processed by Symfony Forms.
 */
class NormalizeRequestData implements ProcessorInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /**
     * @param ValueNormalizer $valueNormalizer
     * @param EntityIdTransformerInterface $entityIdTransformer
     */
    public function __construct(ValueNormalizer $valueNormalizer, EntityIdTransformerInterface $entityIdTransformer)
    {
        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformer = $entityIdTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $requestData = $context->getRequestData();

        if (!array_key_exists(JsonApiDoc::DATA, $requestData)) {
            // the request data are already normalized
            return;
        }

        $data = $requestData[JsonApiDoc::DATA];

        $relations = [];
        if (array_key_exists(JsonApiDoc::RELATIONSHIPS, $data)) {
            $requestType = $context->getRequestType();
            foreach ($data[JsonApiDoc::RELATIONSHIPS] as $name => $value) {
                $relationData = $value[JsonApiDoc::DATA];

                // Relation data can be null in case -to-one and an empty array in case -to-many relation.
                // In this case we should process this relation data as empty relation
                if (null === $relationData || empty($relationData)) {
                    $relations[$name] = [];
                    continue;
                }

                if (array_keys($relationData) !== range(0, count($relationData) - 1)) {
                    $relations[$name] = $this->normalizeItemData($relationData, $requestType);
                } else {
                    foreach ($relationData as $collectionItem) {
                        $relations[$name][] = $this->normalizeItemData($collectionItem, $requestType);
                    }
                }
            }
        }

        $resultData = !empty($data[JsonApiDoc::ATTRIBUTES])
            ? array_merge($data[JsonApiDoc::ATTRIBUTES], $relations)
            : $relations;
        $context->setRequestData($resultData);
    }

    /**
     * @param array       $data ['type' => entity type, 'id' => entity id]
     * @param RequestType $requestType
     *
     * @return array ['class' => entity class, 'id' => entity id]
     */
    protected function normalizeItemData(array $data, RequestType $requestType)
    {
        $entityClass = ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $data[JsonApiDoc::TYPE],
            $requestType,
            true
        );
        $entityId = $this->entityIdTransformer->reverseTransform($entityClass, $data[JsonApiDoc::ID]);

        return [
            'class' => $entityClass,
            'id'    => $entityId
        ];
    }
}
