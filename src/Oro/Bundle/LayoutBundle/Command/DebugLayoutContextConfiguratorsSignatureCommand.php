<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Command;

use Oro\Bundle\LayoutBundle\Command\Util\DebugOptionsResolverDecorator;
use Oro\Bundle\LayoutBundle\Command\Util\DebugSymfonyOptionsResolverDecorator;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Displays context configurators list.
 */
class DebugLayoutContextConfiguratorsSignatureCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:debug:layout:context-configurators';

    public function __construct(
        private array $contextConfigurators,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure()
    {
        $this
            ->setDescription('Displays context configurators list.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command displays layout context configurators list

  <info>php %command.full_name%</info>
HELP
            );
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output)
    {
        sort($this->contextConfigurators);
        $configs = [];
        /** @var ContextConfiguratorInterface $contextConfigurator */
        foreach ($this->contextConfigurators as $contextConfiguratorInfo) {
            $context = new LayoutContext();
            $contextConfiguratorInfo['service']->configureContext($context);
            $resolver = new DebugSymfonyOptionsResolverDecorator($context->getResolver());
            $configs[$contextConfiguratorInfo['id']] = [
                'class' => \get_class($contextConfiguratorInfo['service']),
                'options' => [],
            ];
            foreach ($resolver->getOptions() as $option) {
                $configs[$contextConfiguratorInfo['id']]['options'][$option['name']] = [
                    'required' => $option['required'],
                    'defaultValue' => $this->formatDefaultOptionValue($option['defaultValue'])
                ];
            }
        }

        $yml = Yaml::dump($configs, 8);
        $output->write($yml);

        return static::SUCCESS;
    }

    protected function formatDefaultOptionValue($value): string
    {
        if ($value === DebugOptionsResolverDecorator::NO_VALUE) {
            return '';
        }

        return is_string($value) ? '"' . $value . '"' : json_encode($value);
    }
}
