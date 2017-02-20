<?php

namespace Oro\Bundle\EntityConfigBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class AttributeFamilyGroups extends Constraint
{
    /**
     * @var string
     */
    public $emptyGroupsMessage = 'oro.entity_config.validator.attribute_family.empty_groups';

    /**
     * @var string
     */
    public $manyDefaultGroupsMessage = 'oro.entity_config.validator.attribute_family.many_default_groups';

    /**
     * @var string
     */
    public $defaultGroupIsNotExistMessage = 'oro.entity_config.validator.attribute_family.default_group_is_not_exist';

    /**
     * @var string
     */
    public $sameLabelsMessage = 'oro.entity_config.validator.attribute_family.same_labels';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return AttributeFamilyGroupsValidator::ALIAS;
    }
}
