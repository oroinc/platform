<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Yaml\Yaml;

class LoadConfigValue extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const FILENAME = 'config_value.yml';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // Config uses non-default manager
        $manager = $this->container->get('doctrine')->getManagerForClass(Config::class);

        $config = $this->getConfig($manager);

        foreach ($this->getConfigValuesData() as $name => $data) {
            $configValue = new ConfigValue();
            $configValue->setConfig($config)
                ->setName($name)
                ->setSection($data['section'])
                ->setValue($data['value']);

            $manager->persist($configValue);
            $this->addReference($name, $configValue);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getConfigValuesData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/'.static::FILENAME));
    }

    /**
     * @param ObjectManager $manager
     * @return null|Config
     */
    protected function getConfig(ObjectManager $manager)
    {
        return $manager->getRepository(Config::class)->findOneBy([]);
    }
}
