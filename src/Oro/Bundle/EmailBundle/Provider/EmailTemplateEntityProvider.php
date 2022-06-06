<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;

/**
 * Entity provider that used at the {@see \Oro\Bundle\EmailBundle\Form\Type\EmailTemplateEntityChoiceType} form type.
 */
class EmailTemplateEntityProvider extends EntityProvider
{
    /**
     * {@inheritDoc}
     */
    protected function addEntities(array &$result, $applyExclusions, $translate)
    {
        parent::addEntities($result, $applyExclusions, $translate);

        $config = $this->entityConfigProvider->getConfig(Email::class);
        $this->addEntity(
            $result,
            $config->getId()->getClassName(),
            $config->get('label'),
            $config->get('plural_label'),
            $config->get('icon'),
            $translate
        );
    }
}
