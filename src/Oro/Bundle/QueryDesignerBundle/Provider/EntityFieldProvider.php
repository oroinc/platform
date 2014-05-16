<?php

namespace Oro\Bundle\QueryDesignerBundle\Provider;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\Translator;


use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\EntityManager;


use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider as ParentEntityFieldProvider;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager as QueryDesignerManager;

class EntityFieldProvider extends ParentEntityFieldProvider
{
    /** @var QueryDesignerManager */
    protected $queryDesignerManager;

    public function __construct(
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider,
        EntityClassResolver $entityClassResolver,
        ManagerRegistry $doctrine,
        Translator $translator,
        $virtualFields,
        $hiddenFields,
        QueryDesignerManager $qdManager
    ) {
        parent::__construct(
            $entityConfigProvider,
            $extendConfigProvider,
            $entityClassResolver,
            $doctrine,
            $translator,
            $virtualFields,
            $hiddenFields
        );

        $this->queryDesignerManager = $qdManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        $result = parent::isIgnoredField($metadata, $fieldName);

        if (!$result) {
            $result = $this->queryDesignerManager->isIgnored($metadata->getReflectionClass()->getName(), $fieldName)
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        $result = parent::isIgnoredRelation($metadata, $associationName);

        return $result;
    }
} 