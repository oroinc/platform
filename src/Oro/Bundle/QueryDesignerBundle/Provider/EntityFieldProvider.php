<?php

namespace Oro\Bundle\QueryDesignerBundle\Provider;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\Translator;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider as ParentEntityFieldProvider;
use Oro\Bundle\EntityBundle\Provider\ExcludeFieldProvider;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager as QueryDesignerManager;

class EntityFieldProvider extends ParentEntityFieldProvider
{
    /** @var QueryDesignerManager */
    protected $queryDesignerManager;

    /** @var string */
    protected $queryType;

    /** @var ExcludeFieldProvider */
    protected $excludeFieldProvider;

    public function __construct(
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider,
        EntityClassResolver $entityClassResolver,
        ManagerRegistry $doctrine,
        Translator $translator,
        VirtualFieldProviderInterface $virtualFieldProvider,
        $hiddenFields,
        QueryDesignerManager $qdManager,
        ExcludeFieldProvider $excludeFieldProvider
    ) {
        parent::__construct(
            $entityConfigProvider,
            $extendConfigProvider,
            $entityClassResolver,
            $doctrine,
            $translator,
            $virtualFieldProvider,
            $hiddenFields,
            $excludeFieldProvider
        );

        $this->queryDesignerManager = $qdManager;
        $this->excludeFieldProvider = $excludeFieldProvider;
    }

    /**
     * @param string $queryType
     */
    public function setQueryType($queryType)
    {
        $this->queryType = $queryType;
    }

    /**
     * {@inheritdoc}
     */
    protected function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        $result = parent::isIgnoredField($metadata, $fieldName);

        if (!$result) {
            $excludeRules = $this->queryDesignerManager->getExcludeRules();

            $result = $this->excludeFieldProvider->isIgnoreField($metadata, $fieldName, $excludeRules);
        }

        if (!$result) {
            foreach ($excludeRules as $rule) {
                if ($rule['query_type'] === $this->queryType) {
                    return true;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        $result = parent::isIgnoredRelation($metadata, $associationName);

        if (!$result) {
            $result = $this->isIgnoredField($metadata, $associationName);
        }

        return $result;
    }
}
