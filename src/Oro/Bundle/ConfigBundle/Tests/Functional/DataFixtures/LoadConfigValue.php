<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Symfony\Component\Yaml\Yaml;

class LoadConfigValue extends AbstractFixture
{
    const FILENAME = 'config_value.yml';

    public function load(ObjectManager $manager)
    {
        $config = $this->getConfig(
            $this->getObjectManagerForClass(Config::class)
        );

        $configValueObjectManager = $this->getObjectManagerForClass(ConfigValue::class);

        foreach ($this->getConfigValuesData() as $name => $data) {
            $configValue = new ConfigValue();
            $configValue->setConfig($config)
                ->setName($name)
                ->setSection($data['section'])
                ->setValue($data['value']);

            $configValueObjectManager->persist($configValue);
            $this->setReference($name, $configValue);
        }

        $configValueObjectManager->flush();
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
