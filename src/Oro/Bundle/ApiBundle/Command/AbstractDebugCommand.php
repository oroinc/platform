<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\ApiBundle\Request\RequestType;

abstract class AbstractDebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption(
                'request-type',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The request type. Use <comment>"any"</comment> to ignore the request type.',
                $this->getDefaultRequestType()
            );
    }

    /**
     * @return string[]
     */
    protected function getDefaultRequestType()
    {
        return [RequestType::REST, RequestType::JSON_API];
    }

    /**
     * @param InputInterface $input
     *
     * @return RequestType
     */
    protected function getRequestType(InputInterface $input)
    {
        $value = $input->getOption('request-type');
        if (count($value) === 1 && 'any' === $value[0]) {
            $value = [];
        }

        return new RequestType($value);
    }
}
