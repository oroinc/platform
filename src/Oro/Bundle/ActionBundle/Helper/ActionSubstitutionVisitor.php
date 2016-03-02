<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\ActionBundle\Model\Action;

class ActionSubstitutionVisitor implements SubstitutionVisitorInterface
{
    /**
     * {@inheritdoc}
     */
    public function visit($target, $replacement, $targetKey, $replacementKey)
    {
        if ($replacement instanceof Action && $target instanceof Action) {
            $replacement->setOriginName($targetKey);
            $replacement->getDefinition()->setOrder($target->getDefinition()->getOrder());
        }
    }
}
