<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Schema\v1_4_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Migration versions of previous platform version(4.1) cannot be larger than version of current platform,
 * it is BC break.
 *
 * See: Oro\Bundle\LocaleBundle\Migrations\Schema\v1_4_3\AddRtlModeToLocalizationEntity.
 */
class AddRtlModeToLocalizationEntity implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
    }
}
