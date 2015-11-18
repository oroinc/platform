<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;

class DebugAPICommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:api:debug')
            ->setDescription('Debug entity API configuration.')
            ->addArgument('entity', InputArgument::REQUIRED, 'The entity class name')
            ->addArgument('version', InputArgument::OPTIONAL, 'API version')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $entity = $input->getArgument('entity');
        $apiVersion = $input->getArgument('version') ? : Version::LATEST;

        $processorBag = $this->getContainer()->get('oro_api.action_processor_bag');
        $processor = $processorBag->getProcessor('get_list');
        /** @var GetListContext $context */
        $context = $processor->createContext();
        $context->setRequestType(RequestType::REST);
        $context->setClassName($entity);
        $context->setVersion($apiVersion);
        $context->setLastGroup('initialize');
        $processor->process($context);

        $result[$entity] = $context->getConfig();
        $result[$entity]['filters'] = $context->getConfigOfFilters();

        $output->write(Yaml::dump($result, 5));
    }
}
