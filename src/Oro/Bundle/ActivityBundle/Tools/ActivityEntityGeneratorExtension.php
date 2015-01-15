<?php

namespace Oro\Bundle\ActivityBundle\Tools;

use CG\Generator\PhpClass;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;

class ActivityEntityGeneratorExtension extends AbstractAssociationEntityGeneratorExtension
{
    /** @var ConfigProvider */
    protected $groupingConfigProvider;

    /**
     * @param ConfigProvider $groupingConfigProvider
     */
    public function __construct(ConfigProvider $groupingConfigProvider)
    {
        $this->groupingConfigProvider = $groupingConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        if (!$this->groupingConfigProvider->hasConfig($schema['class'])) {
            return false;
        }

        $groups = $this->groupingConfigProvider->getConfig($schema['class'])->get('groups');

        return
            !empty($groups)
            && in_array(ActivityScope::GROUP_ACTIVITY, $groups);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $schema, PhpClass $class)
    {
        $class->addInterfaceName('Oro\Bundle\ActivityBundle\Model\ActivityInterface');

        parent::generate($schema, $class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationKind()
    {
        return ActivityScope::ASSOCIATION_KIND;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationType()
    {
        return RelationType::MANY_TO_MANY;
    }
}
