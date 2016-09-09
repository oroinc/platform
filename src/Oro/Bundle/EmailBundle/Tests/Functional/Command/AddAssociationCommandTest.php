<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;

use Oro\Bundle\EmailBundle\Command\AddAssociationCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AddAssociationCommandTest extends WebTestCase
{
    /** @var Application */
    protected $application;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            'Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailWithoutActivityData'
        ]);

        $kernel = self::getContainer()->get('kernel');
        $this->application = new Application($kernel);
        $this->application->add(new AddAssociationCommand());
    }

    public function testCheckRunDuplicateJob()
    {
        $command = $this->application->find(AddAssociationCommand::COMMAND_NAME);
        $commandTester = new CommandTester($command);

        $user = self::getReference('simple_user2');

        $doctrine = self::getContainer()->get('doctrine');
        $emails = $doctrine->getRepository('OroEmailBundle:Email')->findAll();

        $emailsId = [];
        foreach ($emails as $email) {
            self::assertFalse($email->hasActivityTarget($user));
            $emailsId[] = $email->getId();
        }

        $commandTester->execute([
            'command' => $command->getName(),
            '--id' => $emailsId,
            '--targetClass' => 'Oro\Bundle\UserBundle\Entity\User',
            '--targetId' => $user->getId()
        ]);

        foreach ($emails as $email) {
            self::assertTrue($email->hasActivityTarget($user));
        }
    }
}
