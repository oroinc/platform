<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\Tools\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder;

//class AttachmentsConfigDumperExtension extends AbstractEntityConfigDumperExtension

class AttachmentsConfigDumperExtension extends AssociationEntityConfigDumperExtension
{
    /** @var  RelationBuilder */
    protected $relationBuilder;

    /** @var  AssociationBuilder */
    protected $associationBuilder;

    public function __construct(
        RelationBuilder $relationBuilder,
        AssociationBuilder $associationBuilder
    ) {
        $this->relationBuilder    = $relationBuilder;
        $this->associationBuilder = $associationBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(array &$extendConfigs)
    {
        $a                   = 1;
        $targetEntityConfigs = $this->getTargetEntityConfigs();
        $entityClass         = $this->getAssociationEntityClass();
        foreach ($targetEntityConfigs as $targetEntityConfig) {
            $targenClassName    = $targetEntityConfig->getId()->getClassName();
            $targetRelationName = ExtendHelper::buildAssociationName($targenClassName, null);
            $this->createAssociation($entityClass, $targenClassName);

            /**
             * todo: check this, may be we do not need any additional options
             */
            $this->relationBuilder->updateFieldConfigs(
                $targenClassName,
                $targetRelationName,
                [
                    'attachment'   => [
                        'multiple' => true,
                        'maxsize'  => 10,
                        'width'    => 100,
                        'height'   => 100,
                    ],
                    'importexport' => [
                        'process_as_scalar' => true
                    ]
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationEntityClass()
    {
        return AttachmentScope::ATTACHMENT_ENTITY;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationScope()
    {
        return 'attachment';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationType()
    {
        return 'manyToMany';
    }
}
