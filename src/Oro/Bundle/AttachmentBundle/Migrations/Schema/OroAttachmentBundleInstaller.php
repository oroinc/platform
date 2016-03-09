<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_0\OroAttachmentBundle;
use Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_1\OroAttachmentBundle as OroAttachmentBundle1;
use Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_2\OroAttachmentBundle as OroAttachmentOrganization;
use Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_4\AddOriginalFilenameIndex;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAttachmentBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_4';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroAttachmentBundle::createFileTable($schema);
        OroAttachmentBundle1::createAttachmentTable($schema);
        OroAttachmentOrganization::addOrganizationFields($schema);
        AddOriginalFilenameIndex::addOriginalFilenameIndex($schema);
    }
}
