<?php

namespace Oro\Bundle\NoteBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Bundle\NoteBundle\Migrations\Schema\v1_0\OroNoteBundle;
use Oro\Bundle\NoteBundle\Migrations\Schema\v1_1\OroNoteBundle as NoteOrganization;

class OroNoteBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroNoteBundle::addNoteTable($schema);
        NoteOrganization::addOrganizationFields($schema);
    }
}
