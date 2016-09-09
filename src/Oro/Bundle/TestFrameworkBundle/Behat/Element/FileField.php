<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Testwork\Suite\Suite;

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
     * Try to find file in Fixtures folder of current suite
     *
     * @param string $filename Filename of attached file with extension e.g. charlie-sheen.jpg
     * @return string Absolute path to file
     *                e.g. /home/charlie/www/orocrm/src/Oro/UserBundle/Tests/Behat/Feature/Fixtures/charlie-sheen.jpg
     */
    protected function getFilePath($filename)
    {
        $suitePaths = $this->suite->getSetting('paths');

        foreach ($suitePaths as $suitePath) {
            $path = $suitePath.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.$filename;

            if (file_exists($path)) {
                return $path;
            }
        }

        self::fail(sprintf('Can\'t find "%s" file in "%s"', $filename, implode(',', $suitePaths)));
    }
}
