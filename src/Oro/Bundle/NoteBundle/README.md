OroNoteBundle
===================

The `OroNoteBundle` provide ability to add notes to other entities. The system administrator can manage this functionality on *System / Entities / Entity Management* page.

How to enable notes using migrations
------------------------------------

The following example shows how notes can be enabled for some entity:
``` php
<?php

namespace OroCRM\Bundle\AccountBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

class OroCRMAccountBundle implements Migration, NoteExtensionAwareInterface
{
    /** @var NoteExtension */
    protected $noteExtension;

    /**
     * {@inheritdoc}
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->noteExtension->addNoteAssociation($schema, 'orocrm_account');
    }
}
```
