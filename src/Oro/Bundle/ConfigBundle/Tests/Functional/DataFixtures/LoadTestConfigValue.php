<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadTestConfigValue extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // Config uses non-default manager
        $manager = $this->container->get('doctrine')->getManagerForClass(Config::class);

        $config = new Config();
        $config->setScopedEntity('test');
        $config->setRecordId(1);

        $configValue = new ConfigValue();
        $configValue->setConfig($config)
            ->setName('test_value')
            ->setSection('general')
            ->setValue('test');

        $manager->persist($config);
        $manager->persist($configValue);

        $manager->flush();
    }
}
