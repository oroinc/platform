<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;

use Oro\Bundle\EmailBundle\Command\AddAssociationCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\EmailBundle\Command\UpdateEmailOwnerAssociationsCommand;

/**
 * @dbIsolation
 */
class UpdateEmailOwnerAssociationsCommandTest extends WebTestCase
{
    /** @var Application */
    protected $application;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            'Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData'
        ]);

        $kernel = self::getContainer()->get('kernel');
        $this->application = new Application($kernel);
        $this->application->add(new UpdateEmailOwnerAssociationsCommand());
    }

    public function testCheckRunDuplicateJob()
    {
        $command = $this->application->find(UpdateEmailOwnerAssociationsCommand::COMMAND_NAME);
        $commandTester = new CommandTester($command);

        $user = self::getReference('simple_user');
        $commandTester->execute(array(
            'command' => $command->getName(),
            'class' => 'Oro\Bundle\UserBundle\Entity\User',
            'id' => [$user->getId()]
        ));

        $doctrine = self::getContainer()->get('doctrine');

        $jobs = $doctrine->getRepository('JMS\JobQueueBundle\Entity\Job')->findBy([
            'command' => AddAssociationCommand::COMMAND_NAME
        ], ['id' => 'ASC']);
        $dependences = $jobs[1]->getDependencies();

        self::assertEquals(2, count($jobs));
        self::assertEquals(1, count($dependences));
    }
}
