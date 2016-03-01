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
     * @param Label $value
     *
     * @return string|null
     */
    protected function trans(Label $value)
    {
        $translated = $value->trans($this->translator);

        return !empty($translated)
            ? $translated
            : null;
    }
}
