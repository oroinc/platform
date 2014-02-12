<?php

namespace Oro\Bundle\SearchBundle\Migration\v1_0;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroSearchBundle implements Migration, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        $queries =  [
            "CREATE TABLE oro_search_index_datetime (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, field VARCHAR(250) NOT NULL, value DATETIME NOT NULL, INDEX IDX_459F212A126F525E (item_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_search_index_decimal (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, field VARCHAR(250) NOT NULL, value NUMERIC(10, 2) NOT NULL, INDEX IDX_E0B9BB33126F525E (item_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_search_index_integer (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, field VARCHAR(250) NOT NULL, value INT NOT NULL, INDEX IDX_E04BA3AB126F525E (item_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_search_item (id INT AUTO_INCREMENT NOT NULL, entity VARCHAR(255) NOT NULL, alias VARCHAR(255) NOT NULL, record_id INT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, changed TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX IDX_ENTITY (entity, record_id), INDEX IDX_ALIAS (alias), INDEX IDX_ENTITIES (entity), PRIMARY KEY(id))",
            "CREATE TABLE oro_search_query (id INT AUTO_INCREMENT NOT NULL, entity VARCHAR(250) NOT NULL, query LONGTEXT NOT NULL, result_count INT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id))",

            "ALTER TABLE oro_search_index_datetime ADD CONSTRAINT FK_459F212A126F525E FOREIGN KEY (item_id) REFERENCES oro_search_item (id)",
            "ALTER TABLE oro_search_index_decimal ADD CONSTRAINT FK_E0B9BB33126F525E FOREIGN KEY (item_id) REFERENCES oro_search_item (id)",
            "ALTER TABLE oro_search_index_integer ADD CONSTRAINT FK_E04BA3AB126F525E FOREIGN KEY (item_id) REFERENCES oro_search_item (id)",

            "CREATE TABLE oro_search_index_text (
                    id INT NOT NULL AUTO_INCREMENT,
                    item_id INT NOT NULL,
                    field VARCHAR(250) NOT NULL,
                    value LONGTEXT NOT NULL,
                    PRIMARY KEY (id),
                    INDEX IDX_A0243539126F525E (item_id)
            )
            ENGINE=MyISAM;"
        ];

        // add search fulltext index query
        $connection = $this->container->get('doctrine')->getConnection();
        $config = $connection->getParams();
        $configClasses = $this->container->getParameter('oro_search.engine_orm');
        if (isset($configClasses[$config['driver']])) {
            $className = $configClasses[$config['driver']];
            $queries = array_merge($queries, [$className::getPlainSql()]);
        }

        return $queries;
    }
}
