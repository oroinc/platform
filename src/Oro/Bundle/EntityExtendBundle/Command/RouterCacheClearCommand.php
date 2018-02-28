<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;

/**
 * Renew Symfony routing cache.
 */
class RouterCacheClearCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        if (!$this->getContainer()->has('router')) {
            return false;
        }
        $router = $this->getContainer()->get('router');
        if (!$router instanceof WarmableInterface) {
            return false;
        }

        return parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('router:cache:clear')
            ->setDescription('Clears the routing cache for an application')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> clears the routing cache for a given environment:

  <info>php %command.full_name% --env=prod</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException When route does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $realCacheDir = $this->getContainer()->getParameter('kernel.cache_dir');
        $tmpCacheDir  = $realCacheDir . '_tmp';
        $filesystem   = $this->getContainer()->get('filesystem');

        if (!is_writable($realCacheDir)) {
            throw new \RuntimeException(sprintf('Unable to write in the "%s" directory', $realCacheDir));
        }

        if ($filesystem->exists($tmpCacheDir)) {
            $filesystem->remove($tmpCacheDir);
        }

        $kernel = $this->getContainer()->get('kernel');
        $output->writeln(
            sprintf(
                'Clearing the routing cache for the <info>%s</info> environment',
                $kernel->getEnvironment()
            )
        );

        $this->getContainer()->get('router')->warmUp($tmpCacheDir);

        /** @var SplFileInfo $file */
        foreach (Finder::create()->files()->in($tmpCacheDir) as $file) {
            $filesystem->copy(
                $file->getPathname(),
                $realCacheDir . DIRECTORY_SEPARATOR . $file->getFilename()
            );
        }

        $filesystem->remove($tmpCacheDir);
    }
}
