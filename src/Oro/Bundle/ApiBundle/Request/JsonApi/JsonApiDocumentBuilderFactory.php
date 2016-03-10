<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

class JsonApiDocumentBuilderFactory
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /**
     * @param ValueNormalizer              $valueNormalizer
     * @param EntityIdTransformerInterface $entityIdTransformer
     */
    public function __construct(
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerInterface $entityIdTransformer
    ) {
        $this->valueNormalizer     = $valueNormalizer;
        $this->entityIdTransformer = $entityIdTransformer;
    }

    /**
     * @return JsonApiDocumentBuilder
     */
    public function createDocumentBuilder()
    {
        return new JsonApiDocumentBuilder(
            $this->valueNormalizer,
            $this->entityIdTransformer
        );
    }
}
