<?php

namespace Oro\Bundle\CommentBundle\Form\Type;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
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
            $applicable = $this->configManager->getProvider('comment')->getConfig($className)->get('applicable');
            if (!$applicable) {
                return true;
            }
        }

        return parent::isReadOnly($options);
    }
}
