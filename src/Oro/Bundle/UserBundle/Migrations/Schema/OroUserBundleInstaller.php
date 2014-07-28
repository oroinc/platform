<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Bundle\UserBundle\Migrations\Schema\v1_0\OroUserBundle;
use Oro\Bundle\UserBundle\Migrations\Schema\v1_1\UserEmailOrigins;
use Oro\Bundle\UserBundle\Migrations\Schema\v1_2\OroUserBundle as UserAvatars;
use Oro\Bundle\UserBundle\Migrations\Schema\v1_3\OroUserBundle as UserEmailActivities;
use Oro\Bundle\UserBundle\Migrations\Schema\v1_4\AttachmentOwner;
use Oro\Bundle\UserBundle\Migrations\Schema\v1_5\SetOwnerForEmailTemplates as EmailTemplateOwner;

class OroUserBundleInstaller implements
    Installation,
    AttachmentExtensionAwareInterface,
    ActivityExtensionAwareInterface
{
    /** @var AttachmentExtension */
    protected $attachmentExtension;

    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_5';
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroUserBundle::oroAccessGroupTable($schema);
        OroUserBundle::oroAccessRoleTable($schema);
        OroUserBundle::oroSessionTable($schema);
        OroUserBundle::oroUserTable($schema, false, false);
        UserAvatars::addAvatarToUser($schema, $this->attachmentExtension);
        UserAvatars::addOwnerToOroFile($schema);
        AttachmentOwner::addOwnerToAttachment($schema);
        EmailTemplateOwner::addOwnerToOroEmailTemplate($schema);
        OroUserBundle::oroUserAccessGroupTable($schema);
        OroUserBundle::oroUserAccessGroupRoleTable($schema);
        OroUserBundle::oroUserAccessRoleTable($schema);
        OroUserBundle::oroUserApiTable($schema);
        OroUserBundle::oroUserBusinessUnitTable($schema);
        OroUserBundle::oroUserEmailTable($schema);
        OroUserBundle::oroUserStatusTable($schema);
        UserEmailOrigins::oroUserEmailOriginTable($schema);

        OroUserBundle::oroAccessGroupForeignKeys($schema);
        OroUserBundle::oroAccessRoleForeignKeys($schema);
        OroUserBundle::oroUserForeignKeys($schema, false);
        OroUserBundle::oroUserAccessGroupForeignKeys($schema);
        OroUserBundle::oroUserAccessGroupRoleForeignKeys($schema);
        OroUserBundle::oroUserAccessRoleForeignKeys($schema);
        OroUserBundle::oroUserApiForeignKeys($schema);
        OroUserBundle::oroUserBusinessUnitForeignKeys($schema);
        OroUserBundle::oroUserEmailForeignKeys($schema);
        OroUserBundle::oroUserStatusForeignKeys($schema);
        UserEmailOrigins::oroUserEmailOriginForeignKeys($schema);

        OroUserBundle::addOwnerToOroEmailAddress($schema);
        UserEmailActivities::addActivityAssociations($schema, $this->activityExtension);
    }
}
