<?php

namespace Oro\Bundle\NoteBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\MassUpdateEntityConfigQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Entity\Note;

class UpdateNoteActivityScopeConfiguration implements Migration
{
    /**
     * @param Schema   $schema
     * @param QueryBag $queries
     *
     * @return void
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $query = new MassUpdateEntityConfigQuery(
            Note::class,
            ['activity' => ['immutable']],
            [
                'activity' => [
                    'acl'                  => 'oro_note_view',
                    'action_button_widget' => 'oro_add_note_button',
                    'action_link_widget'   => 'oro_add_note_link'
                ],
                'grouping' => [
                    'groups' => [
                        'activity'
                    ]
                ]
            ]
        );
        $queries->addPostQuery($query);
    }
}
