<?php

declare(strict_types=1);

namespace Oro\Bundle\ThemeBundle\Command;

use Oro\Bundle\ThemeBundle\Model\Theme;
use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists available themes.
 */
#[AsCommand(
    name: 'oro:theme:list',
    description: 'Lists available themes.'
)]
class ThemeCommand extends Command
{
    private ThemeRegistry $themeRegistry;

    public function __construct(ThemeRegistry $themeRegistry)
    {
        $this->themeRegistry = $themeRegistry;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command lists available themes.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $themes = $this->themeRegistry->getAllThemes();
        $activeTheme = $this->themeRegistry->getActiveTheme();

        if ($themes) {
            $output->writeln('<info>List of available themes:</info>');
            foreach ($themes as $theme) {
                $this->outputTheme($output, $theme, ($theme === $activeTheme));
            }
        } else {
            $output->writeln('<info>No themes are available.</info>');
        }

        return Command::SUCCESS;
    }

    protected function outputTheme(OutputInterface $output, Theme $theme, bool $isActive): void
    {
        if ($isActive) {
            $output->writeln(sprintf('<comment>%s</comment> (active)', $theme->getName()));
        } else {
            $output->writeln(sprintf('<comment>%s</comment>', $theme->getName()));
        }

        if ($theme->getLabel()) {
            $output->writeln(sprintf(' - <info>label:</info> %s', $theme->getLabel()));
        }

        if ($theme->getLogo()) {
            $output->writeln(sprintf(' - <info>logo:</info> %s', $theme->getLogo()));
        }

        if ($theme->getIcon()) {
            $output->writeln(sprintf(' - <info>icon:</info> %s', $theme->getIcon()));
        }

        if ($theme->getScreenshot()) {
            $output->writeln(sprintf(' - <info>screenshot:</info> %s', $theme->getScreenshot()));
        }

        $output->writeln(sprintf(' - <info>rtl_support:</info> %s', $theme->isRtlSupport() ? 'Yes' : 'No'));
    }
}
