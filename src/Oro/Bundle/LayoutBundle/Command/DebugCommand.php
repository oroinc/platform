<?php
declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Command;

use Oro\Bundle\LayoutBundle\Command\Util\DebugOptionsResolverDecorator;
use Oro\Bundle\LayoutBundle\Command\Util\MethodPhpDocExtractor;
use Oro\Bundle\LayoutBundle\Console\Helper\DescriptorHelper;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\Exception\InvalidArgumentException;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;
use Oro\Component\Layout\LayoutRegistry;
use Oro\Component\Layout\LayoutRegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Displays layout configuration.
 */
class DebugCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:debug:layout';

    private LayoutManager $layoutManager;
    private array $blockTypes;
    private array $dataProviders;
    private MethodPhpDocExtractor $methodPhpDocExtractor;
    private ?LayoutRegistry $layoutRegistry = null;

    public function __construct(
        LayoutManager $layoutManager,
        MethodPhpDocExtractor $methodPhpDocExtractor,
        array $blockTypes = [],
        array $dataProviders = []
    ) {
        $this->layoutManager = $layoutManager;
        $this->blockTypes = $blockTypes;
        $this->dataProviders = $dataProviders;
        $this->methodPhpDocExtractor = $methodPhpDocExtractor;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Show block type configuration')
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, 'Show data provider configuration')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format', 'txt')
            ->setDescription('Displays layout configuration.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command displays layout configuration (default context,
registered context configurators, block types and data providers).

  <info>php %command.full_name%</info>

The <info>--type</info> option can be used to see the specified block type details:

  <info>php %command.full_name% --type=<block-type></info>

The <info>--provider</info> option can be used to see the specified data provider details:

  <info>php %command.full_name% --provider=<data-prodiver></info>

HELP
            )
            ->addUsage('--type=<block-type>')
            ->addUsage('--provider=<data-prodiver>')
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (null === $input->getOption('type') && null === $input->getOption('provider')) {
            $object = null;
            $options['data_providers'] = $this->dataProviders;
            $options['block_types'] = $this->blockTypes;
            // sort data providers and block types alphabetically
            sort($options['data_providers']);
            sort($options['block_types']);
            foreach ($options as $k => $list) {
                if (is_array($options[$k])) {
                    sort($options[$k]);
                }
            }
            $options['context_configurators'] = $this->getContextConfigurators();
            $options['context'] = $this->getContextItems();
        } elseif ($dataProviderName = $input->getOption('provider')) {
            $object = $this->getLayoutRegistry()->findDataProvider($dataProviderName);
            if (null === $object) {
                $io->error('Data provider not found.');

                return 1;
            }
            $options['name'] = $dataProviderName;
            $options['class'] = \get_class($object);
            $options['methods'] = $this->methodPhpDocExtractor->extractPublicMethodsInfo($object);
            if (!array_key_exists('methods', $options)) {
                $io->error('Data provider has no public methods that starts with "get" "has" or "is".');

                return 1;
            }
        } elseif ($blockTypeName = $input->getOption('type')) {
            $registry = $this->getLayoutRegistry();
            try {
                $object = $this->layoutManager->getLayoutFactory()->getType($blockTypeName);
            } catch (InvalidArgumentException $exception) {
                $io->error('Block type not found.');

                return 1;
            }
            $options['class'] = \get_class($object);
            $options['hierarchy'] = $this->getBlockTypeHierarchy($object);
            $options['type_extensions'] = $this->getBlockTypeExtensions($blockTypeName);
            $options['options_resolver'] = $this->getBlockTypeOptionsResolver($blockTypeName, $registry);
        }

        $helper = new DescriptorHelper();
        $options['format'] = $input->getOption('format');
        $helper->describe($io, $object, $options);

        return 0;
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

    private function getBlockTypeHierarchy(
        BlockTypeInterface $blockType
    ): array {
        $registry = $this->getLayoutRegistry();
        $hierarchy = [$blockType->getName()];
        $parentName = $blockType->getParent();
        while ($parentName) {
            array_unshift($hierarchy, $parentName);
            $parentName = $registry->getType($parentName)->getParent();
        }

        return $hierarchy;
    }

    private function getContextConfigurators(): array
    {
        $registry = $this->getLayoutRegistry();
        $contextConfigurators = array_map(
            '\get_class',
            $registry->getContextConfigurators()
        );

        return $contextConfigurators;
    }

    /**
     * @return mixed
     */
    private function getContextItems()
    {
        $registry = $this->getLayoutRegistry();
        $context = new LayoutContext();
        $registry->configureContext($context);
        $context->resolve();
        $class = new \ReflectionClass(LayoutContext::class);
        $property = $class->getProperty('items');
        $property->setAccessible(true);

        return $property->getValue($context);
    }

    private function getBlockTypeExtensions(string $blockTypeName): array
    {
        $registry = $this->getLayoutRegistry();

        return array_map(
            '\get_class',
            $registry->getTypeExtensions($blockTypeName)
        );
    }

    private function getLayoutRegistry(): LayoutRegistry
    {
        if (!$this->layoutRegistry) {
            $this->layoutRegistry = $this->layoutManager->getLayoutFactory()->getRegistry();
        }

        return $this->layoutRegistry;
    }
}
