<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Migrations\Schema\v7_1_0_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EmailBundle\Migration\SetEmailAvailableInTemplateQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Disables the password reset confirmation token in email templates and marks it as immutable,
 * so it cannot be re-enabled through the UI.
 */
class DisableFieldsInEmailTemplates implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new SetEmailAvailableInTemplateQuery(
            entityClass: User::class,
            availableInTemplate: false,
            fieldNames: ['confirmationToken'],
            force: true,
            immutable: true
        ));
    }
}
