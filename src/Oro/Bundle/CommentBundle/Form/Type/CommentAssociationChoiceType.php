<?php

namespace Oro\Bundle\CommentBundle\Form\Type;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\AssociationChoiceType;

class CommentAssociationChoiceType extends AssociationChoiceType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_comment_association_choice';
    }

    /**
     * {@inheritdoc}
     */
    protected function isReadOnly($options)
    {
        /** @var EntityConfigId $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();

        if (!empty($className)) {
            $groups = $this->configManager->getProvider('grouping')->getConfig($className)->get('groups');
            if (empty($groups) || !in_array(ActivityScope::GROUP_ACTIVITY, $groups)) {
                return true;
            }
        }

        return parent::isReadOnly($options);
    }
}
