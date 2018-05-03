<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Provider\ChainSearchProvider;
use Oro\Bundle\ConfigBundle\Tests\Unit\Provider\AbstractProviderTest;
use Oro\Bundle\UserBundle\Provider\UserConfigurationFormProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UserConfigurationFormProviderTest extends AbstractProviderTest
{
    const CONFIG_NAME = 'user_configuration';

    /**
     * {@inheritdoc}
     */
    public function getParentCheckboxLabel()
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
    ) {
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
     * Return correct path to fileName
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function getFilePath($fileName)
    {
        return __DIR__ . '/data/' . $fileName;
    }
}
