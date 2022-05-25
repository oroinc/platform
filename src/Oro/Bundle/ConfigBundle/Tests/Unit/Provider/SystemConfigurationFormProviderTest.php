<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Provider\AbstractProvider;
use Oro\Bundle\ConfigBundle\Provider\ChainSearchProvider;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SystemConfigurationFormProviderTest extends AbstractProviderTest
{
    protected const CONFIG_SCOPE = 'app';
    protected const TREE_NAME = 'system_configuration';

    /**
     * {@inheritdoc}
     */
    protected function getParentCheckboxLabel(): string
    {
        return 'oro.config.system_configuration.use_default';
    }

    /**
     * {@inheritdoc}
     */
    public function getProvider(
        ConfigBag $configBag,
        TranslatorInterface $translator,
        FormFactoryInterface $formFactory,
        FormRegistryInterface $formRegistry,
        AuthorizationCheckerInterface $authorizationChecker,
        ChainSearchProvider $searchProvider,
        FeatureChecker $featureChecker,
        EventDispatcherInterface $eventDispatcher
    ): AbstractProvider {
        return new SystemConfigurationFormProvider(
            $configBag,
            $translator,
            $formFactory,
            $formRegistry,
            $authorizationChecker,
            $searchProvider,
            $featureChecker,
            $eventDispatcher
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilePath(string $fileName): string
    {
        return __DIR__ . '/../Fixtures/Provider/' . $fileName;
    }
}
