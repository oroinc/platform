<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Label;

abstract class NormalizeDescription implements ProcessorInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param array  $config
     * @param string $attributeName
     */
    protected function normalizeAttribute(array &$config, $attributeName)
    {
        if (empty($config[$attributeName])) {
            return;
        }
        $attribute = $config[$attributeName];
        if ($attribute instanceof Label) {
            $translated = $attribute->trans($this->translator);
            if (!empty($translated)) {
                $config[$attributeName] = $translated;
            } else {
                unset($config[$attributeName]);
            }
        }
    }
}
