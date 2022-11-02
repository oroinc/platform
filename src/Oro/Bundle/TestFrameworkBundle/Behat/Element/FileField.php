<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Testwork\Suite\Suite;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\OroGenericSuite;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SymfonyBundleSuite;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Behat Element to upload the file to a form
 */
class FileField extends Element implements SuiteAwareInterface
{
    /**
     * @var Suite
     */
    protected $suite;

    /**
     * {@inheritdoc}
     */
    public function setValue($filename)
    {
        $this->attachFile($this->getFilePath($filename));
    }

    /**
     * {@inheritdoc}
     */
    public function setSuite(Suite $suite)
    {
        $this->suite = $suite;
    }

    /**
     * Try to find file in Fixtures folder of current suite,
     * then in TestFrameworkBundle/Tests/Behat/Fixtures
     *
     * @param string $filename Filename of attached file with extension e.g. charlie-sheen.jpg
     * @return string Absolute path to file
     *                e.g. /home/charlie/www/orocrm/src/Oro/UserBundle/Tests/Behat/Feature/Fixtures/charlie-sheen.jpg
     */
    protected function getFilePath($filename)
    {
        $suitePaths = $this->suite->getSetting('paths');
        if (SymfonyBundleSuite::class === get_class($this->suite)) {
            /** @var BundleInterface $bundle */
            $bundle = $this->suite->getBundle();
            $suitePaths[] = sprintf(
                '%s%sTests%2$sBehat%2$sFeatures',
                $bundle->getPath(),
                DIRECTORY_SEPARATOR
            );
        } elseif (OroGenericSuite::class === get_class($this->suite)) {
            $suitePaths[] = sprintf(
                '%s%ssrc%2$sTests%2$sBehat%2$sFeatures',
                $this->suite->getProjectDir(),
                DIRECTORY_SEPARATOR
            );
        }
        $suitePaths[] = dirname(__DIR__, 2) . '/Tests/Behat/';
        foreach ($suitePaths as $suitePath) {
            $suitePath = is_dir($suitePath) ? $suitePath : dirname($suitePath);
            $path = $suitePath.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.$filename;

            if (file_exists($path)) {
                return $path;
            }
        }

        self::fail(sprintf('Can\'t find "%s" file in "%s"', $filename, implode(',', $suitePaths)));
    }
}
