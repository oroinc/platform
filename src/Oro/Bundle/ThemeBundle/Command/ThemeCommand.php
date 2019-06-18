<?php

namespace Oro\Bundle\ThemeBundle\Command;

use Oro\Bundle\ThemeBundle\Model\Theme;
use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to list all available themes
 */
class ThemeCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:theme:list';

    /** @var ThemeRegistry */
    private $themeRegistry;

    /**
     * @param ThemeRegistry $themeRegistry
     */
    public function __construct(ThemeRegistry $themeRegistry)
    {
        $this->themeRegistry = $themeRegistry;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('List of all available themes');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
    }

    /**
     * @param OutputInterface $output
     * @param Theme $theme
     * @param bool $isActive
     */
    protected function outputTheme(OutputInterface $output, Theme $theme, bool $isActive)
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
    }
}
