<?php

namespace Oro\Bundle\ActivityBundle\Tools;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\AbstractAssociationEntityGeneratorExtension;

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
            && in_array(ActivityScope::GROUP_ACTIVITY, $groups)
            && parent::supports($schema);
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
        return 'manyToMany';
    }
}
