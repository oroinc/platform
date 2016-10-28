<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class TranslationFile
{
    /** @var  string */
    private $realPath;

    /** @var array */
    private $translations = [];

    /** @var bool */
    private $dry;

    public function __construct($realPath, $dry = false)
    {
        $this->realPath = $realPath;
        $this->dry = $dry;
    }

    public function addTranslation($key, $value)
    {
        $this->translations[$key] = $value;
    }

    private function getExistingTranslations()
    {
        if (file_exists($this->realPath)) {
            $messages = Yaml::parse(file_get_contents($this->realPath));
            KeysUtil::flatten($messages);

            return $messages;
        } else {
            return [];
        }
    }

    public function dump($flatten = true, LoggerInterface $logger = null)
    {
        $logger = $logger ?: new NullLogger();

        if (count($this->translations) === 0) {
            $logger->debug('Nothing to dump into {file}. Skipping.', ['file' => $this->realPath]);

            return;
        }

        $logger->debug('Fetching existing translations in file {file}', ['file' => $this->realPath]);
        $existing = $this->getExistingTranslations();

        $logger->debug(
            'Merging existing translations with new one.',
            ['existing' => $existing, 'new' => $this->translations]
        );

        $translations = array_merge($existing, $this->translations);

        $yaml = $flatten ? Yaml::dump($translations) :
            Yaml::dump(KeysUtil::expandToTree($translations), 20);

        if ($this->dry) {
            $logger->info('Dry mode. Dump of translations into {file} was not performed.', ['file' => $this->realPath]);

            return;
        }

        (new Filesystem())->dumpFile($this->realPath, $yaml);
        $logger->info('Translations successfully dumped into {file}', ['file' => $this->realPath]);
    }
}
