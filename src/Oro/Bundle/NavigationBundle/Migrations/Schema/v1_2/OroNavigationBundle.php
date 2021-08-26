<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

class OroNavigationBundle implements Migration, ContainerAwareInterface
{
    /** @var UrlMatcherInterface */
    private $urlMatcher;

    /** @var EntityManagerInterface */
    private $em;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->urlMatcher = $container->get('router');
        $this->em = $container->get('doctrine.orm.entity_manager');
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
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
        $queryBuilder = $this->em
            ->getRepository('OroNavigationBundle:NavigationHistoryItem')
            ->createQueryBuilder('h')
            ->select('h.id, h.url');

        $paginator = new Paginator($queryBuilder, false);

        foreach ($paginator as $navItem) {
            try {
                $url = str_replace('index_dev.php/', '', $navItem['url']);
                $routeData = $this->urlMatcher->match($url);
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
