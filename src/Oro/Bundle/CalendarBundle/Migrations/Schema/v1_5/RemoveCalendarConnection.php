<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migration\DeleteAclMigrationQuery;

class RemoveCalendarConnection implements
    Migration,
    OrderedMigrationInterface,
    ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->dropTable('oro_calendar_connection');

        $calendarConnectionClass = 'Oro\Bundle\CalendarBundle\Entity\CalendarConnection';
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config_field WHERE entity_id IN ('
                . 'SELECT id FROM oro_entity_config WHERE class_name = :class)',
                ['class' => $calendarConnectionClass],
                ['class' => 'string']
            )
        );
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config WHERE class_name = :class',
                ['class' => $calendarConnectionClass],
                ['class' => 'string']
            )
        );
        $queries->addPostQuery(
            new DeleteAclMigrationQuery(
                $this->container,
                new ObjectIdentity('entity', $calendarConnectionClass)
            )
        );
    }
}
