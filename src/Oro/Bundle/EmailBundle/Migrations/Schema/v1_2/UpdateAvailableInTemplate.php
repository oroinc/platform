<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class UpdateAvailableInTemplate implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(
            new UpdateAvailableInTemplateQuery(
                $this->container->get('oro_entity_config.metadata.annotation_metadata_factory')
            )
        );
    }
}
