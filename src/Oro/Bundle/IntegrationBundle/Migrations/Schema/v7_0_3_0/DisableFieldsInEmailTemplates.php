<?php

declare(strict_types=1);

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v7_0_3_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EmailBundle\Migration\SetEmailAvailableInTemplateQuery;
use Oro\Bundle\IntegrationBundle\Entity\WebhookConsumerSettings;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
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
                entityClass: WebhookConsumerSettings::class,
                availableInTemplate: false
            )
        );
        $queries->addQuery(
            new SetEmailAvailableInTemplateQuery(
                entityClass: WebhookProducerSettings::class,
                availableInTemplate: false
            )
        );
    }
}
