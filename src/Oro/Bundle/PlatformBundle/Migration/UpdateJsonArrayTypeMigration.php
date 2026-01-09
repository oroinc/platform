<?php

namespace Oro\Bundle\PlatformBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PlatformBundle\Migrations\Schema\Query\UpdateEntityConfigSchemaQuery;

/**
 * Performs database migration to update JSON array field configuration.
 */
class UpdateJsonArrayTypeMigration implements Migration
{
    /** @var FieldConfigModel[] */
    private array $jsonArrayFields;

    /**
     * @param FieldConfigModel[] $jsonArrayFields
     */
    public function __construct(array $jsonArrayFields)
    {
        $this->jsonArrayFields = $jsonArrayFields;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new UpdateEntityConfigSchemaQuery($this->jsonArrayFields));
    }
}
