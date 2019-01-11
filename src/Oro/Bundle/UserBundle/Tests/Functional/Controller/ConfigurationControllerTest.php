<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Controller;

use Oro\Bundle\ConfigBundle\Tests\Functional\Controller\AbstractConfigurationControllerTest;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class ConfigurationControllerTest extends AbstractConfigurationControllerTest
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestUrl(array $parameters)
    {
        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $parameters['id'] = $user->getId();

        return $this->getUrl('oro_user_config', $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return [
            'main user configuration page' => [
                'parameters' => [
                    'activeGroup' => null,
                    'activeSubGroup' => null,
                ],
                'expected' => ['Location options', 'Primary Location']
            ],
            'user configuration sub page' => [
                'parameters' => [
                    'activeGroup' => 'platform',
                    'activeSubGroup' => 'look_and_feel',
                ],
                'expected' => ['Sidebar settings']
            ],
        ];
    }
}
