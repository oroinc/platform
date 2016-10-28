<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

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

    public function dump($flatten = true)
    {
        if ($this->dry) {
            return;
        }
        if (count($this->translations) === 0) {
            //nothing to dump
            return;
        }

        $existing = $this->getExistingTranslations();

        $translations = array_merge($existing, $this->translations);

        $yaml = $flatten ? Yaml::dump($translations) :
            Yaml::dump(KeysUtil::expandToTree($translations), 20);

        (new Filesystem())->dumpFile($this->realPath, $yaml);
    }
}
