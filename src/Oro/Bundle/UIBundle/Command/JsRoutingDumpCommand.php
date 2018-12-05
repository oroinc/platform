<?php

namespace Oro\Bundle\UIBundle\Command;

use FOS\JsRoutingBundle\Command\DumpCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dump routes to use from JavaScript
 */
class JsRoutingDumpCommand extends DumpCommand
{
    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // override default target file
        if (!$input->getOption('target')) {
            $webRootDir = $this->getContainer()->getParameter('kernel.project_dir');
            if ($webRootDir) {
                $input->setOption('target', $webRootDir . '/public/js/routes.js');
            }
        }

        parent::initialize($input, $output);
    }
}
