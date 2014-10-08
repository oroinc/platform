<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;

class OroNavigationBundle implements Migration, ContainerAwareInterface
{
    /** @var Router */
    protected $router;

    /** @var EntityManagerInterface */
    protected $em;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->router = $container->get('router');
        $this->em     = $container->get('doctrine.orm.entity_manager');
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_navigation_history');

        $table->addColumn('route', Type::STRING, ['length' => 128]);
        $table->addColumn('route_parameters', Type::TARRAY, ['comment' => '(DC2Type:array)']);
        $table->addColumn('entity_id', Type::INTEGER, ['notnull' => false]);
        $table->addIndex(['route'], 'oro_navigation_history_route_idx');
        $table->addIndex(['entity_id'], 'oro_navigation_history_entity_id_idx');

        $this->updateNavigationHistory($queries);
    }

    /**
     * Update navigation history with route names and parameters
     *
     * @param QueryBag $queries
     */
    protected function updateNavigationHistory(QueryBag $queries)
    {
        $queryBuilder = $this->em
            ->getRepository('OroNavigationBundle:NavigationHistoryItem')
            ->createQueryBuilder('h')
            ->select('h.id, h.url');

        $paginator = new Paginator($queryBuilder, false);

        foreach ($paginator as $navItem) {
            try {
                $url       = str_replace('app_dev.php/', '', $navItem['url']);
                $routeData = $this->router->match($url);
                $entityId  = isset($routeData['id']) ? (int)$routeData['id'] : null;
                $route     = $routeData['_route'];

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
