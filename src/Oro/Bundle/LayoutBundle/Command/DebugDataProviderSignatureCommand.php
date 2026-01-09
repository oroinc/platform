<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Command;

use Oro\Bundle\LayoutBundle\Command\Util\MethodPhpDocExtractor;
use Oro\Component\Layout\LayoutManager;
use Oro\Component\Layout\LayoutRegistryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Displays data provider signatures.
 */
#[AsCommand(
    name: 'oro:debug:layout:data-providers',
    description: 'Displays data provider signatures.'
)]
class DebugDataProviderSignatureCommand extends Command
{
    private LayoutManager $layoutManager;
    private MethodPhpDocExtractor $methodPhpDocExtractor;
    private array $dataProviders;
    private ?LayoutRegistryInterface $layoutRegistry = null;

    public function __construct(
        LayoutManager $layoutManager,
        MethodPhpDocExtractor $methodPhpDocExtractor,
        array $dataProviders = []
    ) {
        $this->layoutManager = $layoutManager;
        $this->methodPhpDocExtractor = $methodPhpDocExtractor;
        $this->dataProviders = $dataProviders;

        parent::__construct();
    }

    #[\Override]
    public function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command displays function signatures for all data providers

  <info>php %command.full_name%</info>
HELP
            );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->layoutRegistry = $this->layoutManager->getLayoutFactory()->getRegistry();
        $io = new SymfonyStyle($input, $output);

        sort($this->dataProviders);

        $options = [];
        foreach ($this->dataProviders as $dataProviderName) {
            $object = $this->layoutRegistry->findDataProvider($dataProviderName);

            if (null === $object) {
                $io->error('Data provider not found.');

                return static::FAILURE;
            }
            $options['providers'][$dataProviderName] = [
                'class' => \get_class($object),
            ];
            $methodsInfo = $this->methodPhpDocExtractor->extractPublicMethodsInfo($object);
            foreach ($methodsInfo as $methodInfo) {
                $methodName = $methodInfo['name'];
                unset($methodInfo['name']);
                if (isset($methodInfo['return']['type'])
                    && $methodInfo['return']['type'] instanceof \ReflectionType
                ) {
                    $methodInfo['return']['type'] = $methodInfo['return']['type']->__toString();
                }
                if ($methodInfo['return']['type']) {
                    $methodInfo['return']['type'] = ltrim($methodInfo['return']['type'], '\\');
                }
                foreach ($methodInfo['arguments'] ?? [] as $argName => $argumentInfo) {
                    $argumentInfo['type'] = ltrim($argumentInfo['type'], '\\');
                    $methodInfo['arguments'][$argName] = $argumentInfo;
                }
                $options['providers'][$dataProviderName]['methods'][$methodName] = $methodInfo;
            }
        }

        $yml = Yaml::dump($options['providers'], 100);
        $output->write($yml);

        return static::SUCCESS;
    }
}
