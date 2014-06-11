<?php

namespace Oro\Bundle\NoteBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationExtendConfigDumperExtension;
use Oro\Bundle\NoteBundle\Entity\Note;

class NoteExtendConfigDumperExtension extends AssociationExtendConfigDumperExtension
{
    /**
     * {@inheritdoc}
     */
    protected function getAssociationEntityClass()
    {
        return Note::ENTITY_NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationScope()
    {
        return 'note';
    }

    /**
     * {@inheritdoc}
     */
    protected function targetEntityMatch(ConfigInterface $config)
    {
        // Gets the config attribute name which indicates whether the association is enabled or not
        return $config->is('enabled');
    }
}
