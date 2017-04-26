<?php

namespace Oro\Bundle\WindowsBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WindowsBundle\Entity\Repository\WindowsStateRepository;
use Oro\Bundle\WindowsBundle\Entity\WindowsState;

class WindowsStateRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'Oro\Bundle\WindowsBundle\Tests\Functional\DataFixtures\LoadWindowsStateData',
                'Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData',
            ]
        );
    }

    public function testUpdate()
    {
        /** @var WindowsState $state */
        $state = $this->getReference('windows_state.admin');

        $registry = $this->getContainer()->get('doctrine');

        /** @var WindowsStateRepository $repo */
        $repo = $registry->getRepository('OroWindowsBundle:WindowsState');

        $this->assertEquals(1, $repo->update($state->getUser(), $state->getId(), ['cleanUrl' => '/path?a=1']));

        $registry->getManager()->clear();

        /** @var WindowsState $updatedState */
        $updatedState = $repo->find($state->getId());

        $this->assertNotEquals($state->getData(), $updatedState->getData());
    }

    public function testUpdateAnotherUser()
    {
        /** @var WindowsState $state */
        $state = $this->getReference('windows_state.admin');
        /** @var User $user */
        $user = $this->getReference('simple_user');
        $registry = $this->getContainer()->get('doctrine');

        /** @var WindowsStateRepository $repo */
        $repo = $registry->getRepository('OroWindowsBundle:WindowsState');

        $this->assertEquals(0, $repo->update($user, $state->getId(), ['cleanUrl' => '/path?a=1']));
    }

    public function testDelete()
    {
        /** @var WindowsState $state */
        $state = $this->getReference('windows_state.admin');

        $registry = $this->getContainer()->get('doctrine');

        /** @var WindowsStateRepository $repo */
        $repo = $registry->getRepository('OroWindowsBundle:WindowsState');

        $this->assertEquals(1, $repo->delete($state->getUser(), $state->getId()));

        $registry->getManager()->clear();

        $this->assertNull($repo->find($state->getId()));
    }

    public function testDeleteAnotherUser()
    {
        /** @var WindowsState $state */
        $state = $this->getReference('windows_state.admin');
        /** @var User $user */
        $user = $this->getReference('simple_user');
        $registry = $this->getContainer()->get('doctrine');

        /** @var WindowsStateRepository $repo */
        $repo = $registry->getRepository('OroWindowsBundle:WindowsState');

        $this->assertEquals(0, $repo->delete($user, $state->getId()));
    }
}
