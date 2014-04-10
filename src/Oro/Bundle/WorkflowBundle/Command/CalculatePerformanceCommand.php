<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalculatePerformanceCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:performance:test')->addArgument('service', InputArgument::REQUIRED);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serviceName = $input->getArgument('service');

        $content = file_get_contents('/var/www/vhosts/crm_dev/get_log');
        $rows = explode("\n", $content);

        $data = array();
        foreach ($rows as $row) {
            list($service, $memory, $time) = explode("\t", $row);
            $memory = (float)$memory;
            $time = (float)$time;
            if (isset($data[$service])) {
                if ($memory > $data[$service]['memory']) {
                    $data[$service]['memory'] = $memory;
                }
                if ($time > $data[$service]['time']) {
                    $data[$service]['time'] = $time;
                }
            } else {
                $data[$service] = array('memory' => $memory, 'time' => $time);
            }
        }

        $result = '';
        foreach ($data as $service => $stats) {
            $result .= sprintf("%s\t%.3f\t%.3f\n", $service, $stats['memory'], $stats['time']);
        }
        file_put_contents('/var/www/vhosts/crm_dev/get_log_merged', $result);

//        $container = $this->getContainer();
//        if ($container->has($serviceName)) {
//            $memory = memory_get_usage(true);
//            $time = microtime(true);
//
//            $container->get($serviceName);
//
//            $memory = memory_get_usage(true) - $memory;
//            $time = microtime(true) - $time;
//
//            $output->write(sprintf("%s\t%.3f\t%.3f\n", $serviceName, $memory / 1024 / 1024, $time * 1024));
//        }
    }
}
