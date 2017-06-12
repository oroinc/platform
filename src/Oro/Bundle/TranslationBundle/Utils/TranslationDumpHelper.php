<?php

namespace Oro\Bundle\TranslationBundle\Utils;

use Symfony\Component\Translation\MessageCatalogue;

class TranslationDumpHelper
{
    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @var array
     */
    protected $duplicates = [];

    /**
     * @param string    $bundle
     * @param array     $messages
     * @param string    $domain
     * @return TranslationDumpHelper
     */
    public function setMessages($bundle, array $messages, $domain = 'messages')
    {
        $this->messages[$domain][$bundle] = $messages;

        return $this;
    }

    /**
     * @param string            $bundle
     * @param MessageCatalogue  $messageCatalogue
     * @param string            $domain
     * @return MessageCatalogue
     */
    public function removeDuplicate($bundle, MessageCatalogue $messageCatalogue, $domain = 'messages')
    {
        if (isset($this->messages[$domain])) {
            foreach ($this->messages[$domain] as $messages) {
                $data = array_intersect_key($messageCatalogue->all($domain), $messages);
                if (count($data)) {
                    $this->setDuplicate($bundle, $data);
                    $messageCatalogue->replace(array_diff_key($messageCatalogue->all($domain), $data), $domain);
                }
            }
        }

        $this->setMessages($bundle, $messageCatalogue->all($domain), $domain);

        return $messageCatalogue;
    }

    /**
     * Return lang pack location
     *
     * @param string      $systemPath
     * @param string      $projectNamespace
     * @param null|string $bundleName
     *
     * @return string
     */
    public function getLangPackDir($systemPath, $projectNamespace, $bundleName = null)
    {
        $path = $systemPath . $projectNamespace . DIRECTORY_SEPARATOR;

        if (!is_null($bundleName)) {
            $path .= $bundleName . DIRECTORY_SEPARATOR . 'translations';
        }

        return $path;
    }

    /**
     * @param string $key
     * @param array  $data
     *
     * @return TranslationDumpHelper
     */
    protected function setDuplicate($key, array $data)
    {
        $this->duplicates[$key] = isset($this->duplicates[$key])
            ? array_merge($this->duplicates[$key], $data)
            : $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getDuplicates()
    {
        return $this->duplicates;
    }
}
