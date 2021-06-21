<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Provider\AbstractProvider;
use Oro\Bundle\ConfigBundle\Provider\ChainSearchProvider;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SystemConfigurationFormProviderTest extends AbstractProviderTest
{
    protected const CONFIG_NAME = 'system_configuration';

    /**
     * {@inheritdoc}
     */
    public function getParentCheckboxLabel(): string
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
        AuthorizationCheckerInterface $authorizationChecker,
        ChainSearchProvider $searchProvider,
        FormRegistryInterface $formRegistry
    ): AbstractProvider {
        return new SystemConfigurationFormProvider(
            $configBag,
            $translator,
            $formFactory,
            $authorizationChecker,
            $searchProvider,
            $formRegistry
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
