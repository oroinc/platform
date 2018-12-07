<?php

namespace Oro\Bundle\ThemeBundle\Command;

use Oro\Bundle\ThemeBundle\Model\Theme;
use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to list all available themes
 */
class ThemeCommand extends ContainerAwareCommand
{
    /**
     * @var ThemeRegistry
     */
    protected $themeRegistry;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:theme:list')
            ->setDescription('List of all available themes');
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->themeRegistry = $this->getContainer()->get('oro_theme.registry');
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

    protected function outputTheme(OutputInterface $output, Theme $theme, $isActive)
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
