<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Provider\AbstractProvider;
use Oro\Bundle\ConfigBundle\Provider\ChainSearchProvider;
use Oro\Bundle\ConfigBundle\Tests\Unit\Provider\AbstractProviderTest;
use Oro\Bundle\UserBundle\Provider\UserConfigurationFormProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserConfigurationFormProviderTest extends AbstractProviderTest
{
    protected const CONFIG_NAME = 'user_configuration';

    /**
     * {@inheritdoc}
     */
    public function getParentCheckboxLabel(): string
    {
        return 'oro.user.user_configuration.use_default';
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
        return new UserConfigurationFormProvider(
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
        return __DIR__ . '/data/' . $fileName;
    }
}
