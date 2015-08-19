<?php

namespace Oro\Component\Layout\Templating;

use Symfony\Component\Translation\TranslatorInterface;

class TextHelper
{
    const DEFAULT_TRANS_DOMAIN = 'messages';

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
     * Normalizes and translates (if needed) labels in the given value.
     *
     * @param mixed       $value
     * @param string|null $domain
     *
     * @return array
     */
    public function processText($value, $domain = null)
    {
        if (empty($value)) {
            return $value;
        }
        if (is_string($value)) {
            return $this->translator->trans($value, [], $domain ?: self::DEFAULT_TRANS_DOMAIN);
        }

        if (is_array($value)) {
            $this->processArray($value, $domain ?: self::DEFAULT_TRANS_DOMAIN);
        }

        return $value;
    }

    /**
     * @param array       $value
     * @param string|null $domain
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function processArray(&$value, $domain)
    {
        if (isset($value['label'])) {
            $label = $value['label'];
            if (is_string($label)) {
                if (empty($label)) {
                    $value = $label;
                } else {
                    $translatable = isset($value['translatable'])
                        ? $value['translatable']
                        : true;
                    $transDomain  = isset($value['translation_domain'])
                        ? $value['translation_domain']
                        : $domain;

                    if (isset($value['parameters'])) {
                        $params = $value['parameters'];
                        if (!empty($params)) {
                            $this->processArray($params, $domain);
                        }
                    } else {
                        $params = [];
                    }

                    $value = $translatable
                        ? $this->translator->trans($label, $params, $transDomain)
                        : strtr($label, $params);
                }
            } else {
                foreach ($value as &$val) {
                    if (is_array($val)) {
                        $this->processArray($val, $domain);
                    }
                }
            }
        } elseif (array_key_exists('label', $value)) {
            $value = null;
        } else {
            foreach ($value as &$val) {
                if (is_array($val)) {
                    $this->processArray($val, $domain);
                }
            }
        }
    }
}
