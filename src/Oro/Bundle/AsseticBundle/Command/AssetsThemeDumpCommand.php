<?php

namespace Oro\Bundle\AsseticBundle\Command;

use Symfony\Bundle\AsseticBundle\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Oro\Bundle\LayoutBundle\Assetic\LayoutResource;

/**
 * Extends Symfony 'assetic:dump'
 */
class AssetsThemeDumpCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:assetic:dump:theme')
            ->setDescription('Dumps a special asset or theme asset')
            ->addArgument('name', InputArgument::REQUIRED, 'The theme assets')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $themeName = $input->getArgument('name');

        $needle = LayoutResource::RESOURCE_ALIAS.'_'.$themeName.'_';
        foreach ($this->am->getNames() as $name) {
            if (strpos($name, $needle) !== false) {
                $this->dumpAsset($name, $output);
            }
        }
    }
}
