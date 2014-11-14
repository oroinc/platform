<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarPropertyRepository;
use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OroCalendarBundle implements Migration, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
        $table = $schema->getTable('oro_calendar_property');
        $table->getColumn('background_color')->setOptions(['length' => 7]);
        $table->dropColumn('color');

        $this->updateBackgroundColorValues($queries);
    }

    /**
     * Updates backgroundColor fields to full hex format (e.g. '#FFFFFF') in multi-platform way
     *
     * @param QueryBag $queries
     */
    protected function updateBackgroundColorValues(QueryBag $queries)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        /** @var CalendarPropertyRepository $repo */
        $repo = $em->getRepository('OroCalendarBundle:CalendarProperty');
        $query = $repo->createQueryBuilder('c')
            ->where('c.backgroundColor IS NOT NULL')
            ->getQuery();
        /** @var CalendarProperty $item */
        foreach($query->execute() as $item) {
            $queries->addPostQuery(new ParametrizedSqlMigrationQuery('UPDATE oro_calendar_property
              SET background_color = :color WHERE id = :id', [
                'color' => '#' . $item->getBackgroundColor(),
                'id'    => $item->getId(),
            ]));
        }
    }
}
