<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration, NameGeneratorAwareInterface, ExtendExtensionAwareInterface
{
    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @inheritdoc
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * @inheritdoc
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $fromAndRecipientsUser = '
            SELECT DISTINCT email_id, user_id FROM (
            SELECT e.id as email_id,
                ed.owner_user_id as user_id
            FROM oro_email_address ed
            INNER JOIN oro_email e ON e.from_email_address_id = ed.id
            WHERE ed.owner_user_id IS NOT NULL
            UNION
            SELECT DISTINCT er.email_id as email_id, ed.owner_user_id as user_id
            FROM oro_email_address ed
            INNER JOIN oro_email_recipient er ON er.email_address_id=ed.id
            WHERE ed.owner_user_id IS NOT NULL) as subq';

        $fromAndRecipientsContact = '
            SELECT DISTINCT email_id, contact_id FROM (
            SELECT
                e.id as email_id,
                ed.owner_contact_id as contact_id
            FROM oro_email_address ed
            INNER JOIN oro_email e ON e.from_email_address_id = ed.id
            WHERE ed.owner_contact_id IS NOT NULL
            UNION
            SELECT DISTINCT er.email_id as email_id, ed.owner_contact_id as contact_id
            FROM oro_email_address ed
            INNER JOIN oro_email_recipient er ON er.email_address_id=ed.id
            WHERE ed.owner_contact_id IS NOT NULL) as subq';

        $this->migrateEmails('oro_email', 'oro_user', $fromAndRecipientsUser, $queries);
        $this->migrateEmails('oro_email', 'orocrm_contact', $fromAndRecipientsContact, $queries);
    }

    /**
     * @param string   $sourceTableName
     * @param string   $targetTableName
     * @param string   $selectQuery
     * @param QueryBag $queries
     */
    public function migrateEmails($sourceTableName, $targetTableName, $selectQuery, QueryBag $queries)
    {
        $targetClassName = $this->extendExtension->getEntityClassByTableName($targetTableName);
        $selfClassName   = $this->extendExtension->getEntityClassByTableName($sourceTableName);
        $associationName = ExtendHelper::buildAssociationName(
            $targetClassName,
            ActivityScope::ASSOCIATION_KIND
        );

        $tableName = $this->nameGenerator->generateManyToManyJoinTableName(
            $selfClassName,
            $associationName,
            $targetClassName
        );

        $queries->addQuery(sprintf("INSERT INTO %s %s", $tableName, $selectQuery));
    }
}
