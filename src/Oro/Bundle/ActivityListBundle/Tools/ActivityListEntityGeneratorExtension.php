<?php

namespace Oro\Bundle\ActivityListBundle\Tools;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;

class ActivityListEntityGeneratorExtension extends AbstractAssociationEntityGeneratorExtension
{
    /** @var ActivityListChainProvider */
    protected $listProvider;

    /**
     * @param ActivityListChainProvider $listProvider
     */
    public function __construct(ActivityListChainProvider $listProvider)
    {
        $this->listProvider = $listProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        return
            $schema['class'] === ActivityListEntityConfigDumperExtension::ENTITY_CLASS
            && parent::supports($schema);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationKind()
    {
        return ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationType()
    {
        return 'manyToMany';
    }
}
