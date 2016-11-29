<?php

namespace Oro\Bundle\NoteBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RemoveNoteConfigurationScope extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $connection = $manager->getConnection();

        $sql = 'SELECT id, class_name, data FROM oro_entity_config';
        $entityConfigs = $connection->fetchAll($sql);
        $entityConfigs = array_map(function ($entityConfig) use ($connection) {
            $entityConfig['data'] = empty($entityConfig['data'])
                ? []
                : $connection->convertToPHPValue($entityConfig['data'], Type::TARRAY);

            return $entityConfig;
        }, $entityConfigs);

        foreach ($entityConfigs as $entityConfig) {
            unset($entityConfig['data']['note']);
            $connection->executeUpdate(
                'UPDATE oro_entity_config SET data=? WHERE id=?',
                [
                    $connection->convertToDatabaseValue($entityConfig['data'], Type::TARRAY),
                    $entityConfig['id']
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
