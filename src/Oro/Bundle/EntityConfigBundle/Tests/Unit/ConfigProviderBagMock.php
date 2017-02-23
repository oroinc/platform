<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;

class ConfigProviderBagMock extends ConfigProviderBag
{
    /** @var ConfigProvider[] */
    private $configProviders;

    /**
     * @param ConfigProvider[] $configProviders [scope => ConfigProvider, ...]
     */
    public function __construct(array $configProviders = [])
    {
        $this->configProviders = $configProviders;
    }

    /**
     * @param ConfigProvider $configProvider
     * @param string|null    $scope
     */
    public function addProvider(ConfigProvider $configProvider, $scope = null)
    {
        if (!$scope) {
            $scope = $configProvider->getScope();
        }
        $this->configProviders[$scope] = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getProvider($scope)
    {
        return isset($this->configProviders[$scope])
            ? $this->configProviders[$scope]
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getProviders()
    {
        return $this->configProviders;
    }
}
