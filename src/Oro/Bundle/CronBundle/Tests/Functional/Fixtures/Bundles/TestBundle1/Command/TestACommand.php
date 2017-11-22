<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Fixtures\Bundles\TestBundle1\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestACommand extends ContainerAwareCommand implements CronCommandInterface
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:cron:test-a');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return false;
    }
}
