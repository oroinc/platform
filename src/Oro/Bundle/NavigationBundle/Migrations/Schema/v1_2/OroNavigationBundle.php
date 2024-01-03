<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

class OroNavigationBundle implements Migration, ConnectionAwareInterface, ContainerAwareInterface
{
    use ConnectionAwareTrait;
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_navigation_history');
        $table->addColumn('route', Types::STRING, ['length' => 128]);
        $table->addColumn('route_parameters', Types::ARRAY, ['comment' => '(DC2Type:array)']);
        $table->addColumn('entity_id', Types::INTEGER, ['notnull' => false]);
        $table->addIndex(['route'], 'oro_navigation_history_route_idx');
        $table->addIndex(['entity_id'], 'oro_navigation_history_entity_id_idx');

        $this->updateNavigationHistory($queries);
    }

    /**
     * Update navigation history with route names and parameters
     */
    private function updateNavigationHistory(QueryBag $queries): void
    {
        /** @var UrlMatcherInterface $urlMatcher */
        $urlMatcher = $this->container->get('router');
        $navItems = $this->connection->fetchAll('SELECT id, url FROM oro_navigation_history');
        foreach ($navItems as $navItem) {
            try {
                $url = str_replace('index_dev.php/', '', $navItem['url']);
                $routeData = $urlMatcher->match($url);
                $entityId = isset($routeData['id']) ? (int)$routeData['id'] : null;
                $route = $routeData['_route'];

                unset($routeData['_controller'], $routeData['id'], $routeData['_route']);

                $queries->addPostQuery(
                    sprintf(
                        'UPDATE oro_navigation_history ' .
                        'SET route = \'%s\', entity_id = %d, route_parameters=\'%s\' WHERE id = %d',
                        $route,
                        $entityId,
                        serialize($routeData),
                        $navItem['id']
                    )
                );
            } catch (\RuntimeException $e) {
                $queries->addPostQuery(
                    sprintf(
                        'UPDATE oro_navigation_history SET route_parameters = \'%s\' WHERE id=%d',
                        serialize([]),
                        $navItem['id']
                    )
                );
            }
        }
    }
}
