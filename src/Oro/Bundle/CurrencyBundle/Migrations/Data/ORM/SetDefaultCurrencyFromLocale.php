<?php

namespace Oro\Bundle\CurrencyBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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

        if (!$currentCurrency) {
            $configManager->set($currencyConfigKey, $this->getDefaultCurrency());

            $configManager->flush();
        }
    }

    /**
     * @return string
     */
    protected function getDefaultCurrency()
    {
        /** TODO: Should be properly fixed in BAP-14914 */
        if ($this->container->hasParameter('currency')) {
            return $this->container->getParameter('currency');
        }

        return CurrencyConfig::DEFAULT_CURRENCY;
    }
}
