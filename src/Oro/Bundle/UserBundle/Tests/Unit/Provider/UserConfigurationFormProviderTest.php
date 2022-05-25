<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Provider\AbstractProvider;
use Oro\Bundle\ConfigBundle\Provider\ChainSearchProvider;
use Oro\Bundle\ConfigBundle\Tests\Unit\Provider\AbstractProviderTest;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\UserBundle\Provider\UserConfigurationFormProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserConfigurationFormProviderTest extends AbstractProviderTest
{
    protected const CONFIG_SCOPE = 'user';
    protected const TREE_NAME = 'user_configuration';

    /**
     * {@inheritDoc}
     */
    protected function getParentCheckboxLabel(): string
    {
        return 'oro.user.user_configuration.use_default';
    }

    /**
     * {@inheritDoc}
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
        return new UserConfigurationFormProvider(
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
     * {@inheritDoc}
     */
    protected function getFilePath(string $fileName): string
    {
        return __DIR__ . '/data/' . $fileName;
    }
}
