<?php

namespace Oro\Bundle\UIBundle\Command;

use FOS\JsRoutingBundle\Command\DumpCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JsRoutingDumpCommand extends DumpCommand
{
    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // override default target file
        if (!$input->getOption('target')) {
            $webRootDir = $this->getContainer()->getParameter('assetic.read_from');
            if ($webRootDir) {
                $input->setOption('target', $webRootDir . '/js/routes.js');
            }
        }

        parent::initialize($input, $output);
    }
}
