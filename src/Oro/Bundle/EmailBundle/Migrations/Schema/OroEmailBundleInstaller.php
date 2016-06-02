<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_0\OroEmailBundle;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_1\OroEmailBundle as OroEmailBundle11;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_3\OroEmailBundle as OroEmailBundle13;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_4\OroEmailBundle as OroEmailBundle14;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_7\OroEmailBundle as OroEmailBundle17;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_8\OroEmailBundle as OroEmailBundle18;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_9\OroEmailBundle as OroEmailBundle19;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_12\OroEmailBundle as OroEmailBundle112_1;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_12\RemoveOldSchema as OroEmailBundle112_2;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_12\UpdateEmailUser as OroEmailBundle112_3;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_13\OroEmailBundle as OroEmailBundle113;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_14\OroEmailBundle as OroEmailBundle114;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_15\OroEmailBundle as OroEmailBundle115;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_16\OroEmailBundle as OroEmailBundle116_1;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_16\CreateAutoResponse as OroEmailBundle116_2;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_19\OroEmailBundle as OroEmailBundle119;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_20\OroEmailBundle as OroEmailBundle120;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_21\OroEmailBundle as OroEmailBundle121;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_22\OroEmailBundle as OroEmailBundle122;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_23\OroEmailBundle as OroEmailBundle123;
use Oro\Bundle\EmailBundle\Migrations\Schema\v1_24\OroEmailBundle as OroEmailBundle124;

/**
 * Class OroEmailBundleInstaller
 * @package Oro\Bundle\EmailBundle\Migrations\Schema
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OroEmailBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_24';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroEmailBundle::oroEmailTable($schema, true, false);
        OroEmailBundle::oroEmailAddressTable($schema);
        OroEmailBundle::oroEmailAttachmentTable($schema);
        OroEmailBundle::oroEmailAttachmentContentTable($schema);
        OroEmailBundle::oroEmailBodyTable($schema);
        OroEmailBundle::oroEmailFolderTable($schema);
        OroEmailBundle::oroEmailOriginTable($schema);
        OroEmailBundle::oroEmailRecipientTable($schema);
        OroEmailBundle11::oroEmailToFolderRelationTable($schema);

        OroEmailBundle::oroEmailTemplateTable($schema);
        OroEmailBundle::oroEmailTemplateTranslationTable($schema);

        OroEmailBundle::oroEmailForeignKeys($schema, false);
        OroEmailBundle::oroEmailAttachmentForeignKeys($schema);
        OroEmailBundle::oroEmailAttachmentContentForeignKeys($schema);
        OroEmailBundle::oroEmailBodyForeignKeys($schema);
        OroEmailBundle::oroEmailFolderForeignKeys($schema);
        OroEmailBundle::oroEmailRecipientForeignKeys($schema);

        OroEmailBundle::oroEmailTemplateTranslationForeignKeys($schema);

        OroEmailBundle13::addOrganization($schema);

        OroEmailBundle14::addColumns($schema);

        OroEmailBundle17::addTable($schema);
        OroEmailBundle17::addColumns($schema);
        OroEmailBundle17::addForeignKeys($schema);

        OroEmailBundle18::addAttachmentRelation($schema);
        OroEmailBundle19::changeAttachmentRelation($schema);

        OroEmailBundle112_1::changeEmailToEmailBodyRelation($schema);
        OroEmailBundle112_1::splitEmailEntity($schema);
        OroEmailBundle112_2::removeOldSchema($schema);
        OroEmailBundle112_3::updateEmailUser($schema);

        OroEmailBundle113::addColumnMultiMessageId($schema);

        OroEmailBundle114::addEmbeddedContentIdField($schema);

        OroEmailBundle115::addEmailFolderFields($schema);
        OroEmailBundle115::addEmailOriginFields($schema);
        OroEmailBundle115::updateEmailRecipientConstraint($schema);

        OroEmailBundle116_1::createOroEmailMailboxProcessSettingsTable($schema);
        OroEmailBundle116_1::createOroEmailMailboxTable($schema);
        OroEmailBundle116_1::addOwnerMailboxColumn($schema);
        OroEmailBundle116_1::addOroEmailMailboxForeignKeys($schema);
        OroEmailBundle116_1::addEmailUserMailboxOwnerColumn($schema);

        OroEmailBundle116_2::oroEmailAutoResponseRuleTable($schema);
        OroEmailBundle116_2::oroEmailAutoResponseRuleConditionTable($schema);
        OroEmailBundle116_2::oroEmailTemplateTable($schema);
        OroEmailBundle116_2::oroEmailTable($schema);

        OroEmailBundle119::oroEmailUserTable($schema);
        OroEmailBundle120::oroEmailTable($schema);

        OroEmailBundle121::addIndexes($schema);

        OroEmailBundle122::oroEmailFolderTable($schema);

        OroEmailBundle123::oroEmailTable($schema);
        OroEmailBundle124::removeIndex($schema);
    }
}
