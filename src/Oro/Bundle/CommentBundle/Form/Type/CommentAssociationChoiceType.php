<?php

namespace Oro\Bundle\CommentBundle\Form\Type;

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
            $provider = $this->configManager->getProvider('comment');
            if (!$provider->hasConfigById($configId) || !$provider->getConfig($className)->is('enabled')) {
                return true;
            }
        }

        return parent::isReadOnly($options);
    }
}
