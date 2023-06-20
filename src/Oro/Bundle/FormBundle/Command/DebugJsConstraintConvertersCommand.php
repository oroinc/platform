<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Command;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverterInterface;
use ProxyManager\Proxy\LazyLoadingInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command aims to get the list of registered JS constraint converters
 */
class DebugJsConstraintConvertersCommand extends Command
{
    protected static $defaultName = 'oro:debug:form:js-constraint-converters';

    protected static $defaultDescription =
        'Returns the list of registered JS constraint converters in order to priority';

    /** @var iterable|ConstraintConverterInterface[] */
    private iterable $converters;

    public function __construct(iterable $processors)
    {
        parent::__construct();
        $this->converters = $processors;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders([
            'Order',
            'Converter',
        ]);
        foreach ($this->converters as $order => $converter) {
            $table->addRow([
                $order,
                $this->getRealClass($converter),
            ]);
        }
        $table->render();

        return 0;
    }

    private function getRealClass(ConstraintConverterInterface $converter): string
    {
        if ($converter instanceof LazyLoadingInterface) {
            return get_parent_class($converter);
        }

        return get_class($converter);
    }
}
