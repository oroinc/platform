<?php

namespace Oro\Bundle\NoteBundle\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AssociationEntityConfigDumperExtension;

class NoteEntityConfigDumperExtension extends AssociationEntityConfigDumperExtension
{
    /**
     * {@inheritdoc}
     */
    protected function getAssociationEntityClass()
    {
        return 'Oro\Bundle\NoteBundle\Entity\Note';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationScope()
    {
        return 'note';
    }
}
