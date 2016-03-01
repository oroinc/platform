<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Request\EntityClassTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;

class JsonApiDocumentBuilderFactory
{
    /** @var EntityClassTransformerInterface */
    protected $entityClassTransformer;

    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /**
     * @param EntityClassTransformerInterface $entityClassTransformer
     * @param EntityIdTransformerInterface    $entityIdTransformer
     */
    public function __construct(
        EntityClassTransformerInterface $entityClassTransformer,
        EntityIdTransformerInterface $entityIdTransformer
    ) {
        $this->entityClassTransformer = $entityClassTransformer;
        $this->entityIdTransformer    = $entityIdTransformer;
    }

    /**
     * @return JsonApiDocumentBuilder
     */
    public function createDocumentBuilder()
    {
        return new JsonApiDocumentBuilder(
            $this->entityClassTransformer,
            $this->entityIdTransformer
        );
    }
}
