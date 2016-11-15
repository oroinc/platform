<?php

namespace Oro\Bundle\NoteBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class UpdateNoteAssociationKind extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        return;
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $configs = $configManager->getConfigs('note', null, true);

        /** @var ConfigInterface[] $entitiesLinkedWithNotesConfigurations */
        $entitiesLinkedWithNotesConfigurations = array_filter(
            $configs,
            function (ConfigInterface $config) {
                return (bool)$config->get('enabled');
            }
        );

        foreach ($entitiesLinkedWithNotesConfigurations as $entityConfigurations) {
            $className = $entityConfigurations->getId()->getClassName();
            $activityConfigs = $configManager->getEntityConfig('activity', $className);
            $activityConfigValues = $activityConfigs->get('activities');
            $activityConfigValues[] = 'Oro\Bundle\NoteBundle\Entity\Note';
            $activityConfigs->set('activities', $activityConfigValues);

            $configManager->persist($activityConfigs);
        }

        $configManager->flush();

        /** @var ExtendConfigDumper $dumper */
        $dumper = $this->container->get('oro_entity_extend.tools.dumper');
        $dumper->updateConfig(function (ConfigInterface $config) {

        });
    }

    /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
