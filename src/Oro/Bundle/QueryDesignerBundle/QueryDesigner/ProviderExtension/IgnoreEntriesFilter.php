<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner\ProviderExtension;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager as QueryDesignerManager;

class IgnoreEntriesFilter extends EntityFieldProviderExtension
{
    /** @var QueryDesignerManager */
    protected $queryManager;

    public function __construct(QueryDesignerManager $queryManager)
    {
        $this->queryManager = $queryManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        // TODO: Implement isIgnoredField() method.
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        // TODO: Implement isIgnoredRelation() method.
    }
} 