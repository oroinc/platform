<?php

namespace Oro\Bundle\CurrencyBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * This fixture changes application currency in case it is set in 'parameters.yml'
 */
class SetDefaultCurrencyFromLocale extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');
        $currencyConfigKey = CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_DEFAULT_CURRENCY);

        $currentCurrency = $configManager->get($currencyConfigKey);
        $defaultCurrency = $this->getDefaultCurrency();

        if ($currentCurrency !== $defaultCurrency) {
            $configManager->set($currencyConfigKey, $defaultCurrency);

            $configManager->flush();
        }
    }

    /**
     * @return string
     */
    protected function getDefaultCurrency()
    {
        if ($this->container->hasParameter('currency')) {
            return $this->container->getParameter('currency');
        }

        return CurrencyConfig::DEFAULT_CURRENCY;
    }
}
