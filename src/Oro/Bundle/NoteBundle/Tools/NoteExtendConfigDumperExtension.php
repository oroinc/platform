<?php

namespace Oro\Bundle\NoteBundle\Tools;

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
}
