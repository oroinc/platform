<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Command;

use Oro\Bundle\LayoutBundle\Command\Util\DebugOptionsResolverDecorator;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\LayoutManager;
use Oro\Component\Layout\LayoutRegistryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Displays block type list.
 */
#[AsCommand(
    name: 'oro:debug:layout:block-types',
    description: 'Displays block type list.'
)]
class DebugLayoutBlockTypeSignatureCommand extends Command
{
    private LayoutRegistryInterface $registry;

    public function __construct(
        private readonly LayoutManager $layoutManager,
        private array $blockTypes
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command displays layout block type list

  <info>php %command.full_name%</info>
HELP
            );
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        sort($this->blockTypes);

        $this->registry = $this->layoutManager->getLayoutFactory()->getRegistry();
        $types = [];
        foreach ($this->blockTypes as $blockTypeName) {
            $object = $this->layoutManager->getLayoutFactory()->getType($blockTypeName);
            $typeInfo['class'] = \get_class($object);
            $optionsResolver = $this->getBlockTypeOptionsResolver($blockTypeName, $this->registry);
            $typeOptions = [];
            foreach ($optionsResolver->getOptions() as $option) {
                $typeOptions[$option['name']] = [
                    'required' => $option['required'],
                    'defaultValue' => $this->formatDefaultOptionValue($option['defaultValue'])
                ];
            }
            $typeInfo['options'] = $typeOptions;
            $types[$blockTypeName] = $typeInfo;
        }

        $yml = Yaml::dump($types, 8);
        $output->write($yml);

        return static::SUCCESS;
    }

    private function getBlockTypeOptionsResolver(
        string $blockTypeName,
        LayoutRegistryInterface $registry
    ): DebugOptionsResolverDecorator {
        $type = $registry->getType($blockTypeName);
        $parentName = $type->getParent();

        $decorator = $parentName
            ? clone $this->getBlockTypeOptionsResolver($parentName, $registry)
            : new DebugOptionsResolverDecorator(new OptionsResolver());

        $type->configureOptions($decorator->getOptionResolver());
        $registry->configureOptions($blockTypeName, $decorator->getOptionResolver());

        return $decorator;
    }

    protected function formatDefaultOptionValue($value): string
    {
        if ($value === DebugOptionsResolverDecorator::NO_VALUE) {
            return '';
        }

        return is_string($value) ? '"' . $value . '"' : json_encode($value);
    }
}
