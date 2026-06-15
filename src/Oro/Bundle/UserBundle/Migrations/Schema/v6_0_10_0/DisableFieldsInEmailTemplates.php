<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Migrations\Schema\v6_0_10_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EmailBundle\Migration\SetEmailAvailableInTemplateQuery;
use Oro\Bundle\EmailBundle\Migration\SetEmailImmutableQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Disables fields that should not be available in email templates.
 * Marks sensitive fields as immutable to prevent modification through the UI.
 */
class DisableFieldsInEmailTemplates implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new SetEmailAvailableInTemplateQuery(
            entityClass: Impersonation::class,
            availableInTemplate: false,
            immutable: true
        ));
        $queries->addQuery(new SetEmailImmutableQuery(
            entityClass: User::class,
            fieldNames: ['password', 'salt']
        ));
    }
}
