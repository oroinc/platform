<?php

declare(strict_types=1);

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v5_0_23_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\EmailBundle\Migration\SetEmailAvailableInTemplateQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Disables fields that should not be available in email templates.
 */
class DisableFieldsInEmailTemplates implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(
            new SetEmailAvailableInTemplateQuery(
                entityClass: Audit::class,
                availableInTemplate: false,
                immutable: true
            )
        );
    }
}
