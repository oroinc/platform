<?php

namespace Oro\Bundle\AsseticBundle\Command;

use Symfony\Bundle\AsseticBundle\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Extends Symfony 'assetic:dump'
 */
class AssetsDumpCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    const ASSET_EXTENTIONS = [
        '.less',
        '.scss',
        '.js',
        '.css'
    ];

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:assetic:dump')
            ->setDescription('Dumps a special assets by file extension')
            ->addArgument('name', InputArgument::REQUIRED, 'The extension like .less, .css, .js, .scss')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $extName = $input->getArgument('name');
        if (!in_array($extName, self::ASSET_EXTENTIONS, true)) {
            $output->writeln('<error>No assetic extention identifier defined</error>');
            return;
        }
        foreach ($this->am->getNames() as $name) {
            $newList = $this->checkFile($this->am->getFormula($name), $extName);
            $this->am->setFormula($name, $newList);
            $this->dumpAsset($name, $output);
        }
    }

    private function checkFile(array $nameFormula, $extName)
    {
        foreach ($nameFormula[0] as $key => $value) {
            if (strpos($value, $extName) === false) {
                unset($nameFormula[0][$key]);
            }
        }
        return $nameFormula;
    }
}
