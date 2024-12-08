<?php

namespace Oro\Bundle\WindowsBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\WindowsBundle\Entity\Repository\WindowsStateRepository;
use Oro\Bundle\WindowsBundle\Entity\WindowsState;
use Oro\Bundle\WindowsBundle\Tests\Functional\DataFixtures\LoadWindowsStateData;

class WindowsStateRepositoryTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadWindowsStateData::class, LoadUserData::class]);
    }

    public function testUpdate()
    {
        /** @var WindowsState $state */
        $state = $this->getReference('windows_state.admin');

        $registry = $this->getContainer()->get('doctrine');

        /** @var WindowsStateRepository $repo */
        $repo = $registry->getRepository(WindowsState::class);

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
        $repo = $registry->getRepository(WindowsState::class);

        $this->assertEquals(0, $repo->update($user, $state->getId(), ['cleanUrl' => '/path?a=1']));
    }

    public function testDelete()
    {
        /** @var WindowsState $state */
        $state = $this->getReference('windows_state.admin');

        $registry = $this->getContainer()->get('doctrine');

        /** @var WindowsStateRepository $repo */
        $repo = $registry->getRepository(WindowsState::class);

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
        $repo = $registry->getRepository(WindowsState::class);

        $this->assertEquals(0, $repo->delete($user, $state->getId()));
    }
}
