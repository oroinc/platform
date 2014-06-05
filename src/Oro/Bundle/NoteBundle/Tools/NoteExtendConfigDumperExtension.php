<?php

namespace Oro\Bundle\NoteBundle\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\AssociationExtendConfigDumperExtension;

class NoteExtendConfigDumperExtension extends AssociationExtendConfigDumperExtension
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
