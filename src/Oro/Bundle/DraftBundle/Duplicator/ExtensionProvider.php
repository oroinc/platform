<?php

namespace Oro\Bundle\DraftBundle\Duplicator;

/**
 * Chain of draft extensions
 */
class ExtensionProvider
{
    /**
     * @var iterable
     */
    private $extensions;

    /**
     * @param iterable $extensions
     */
    public function __construct(iterable $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * @return array
     */
    public function getExtensions(): iterable
    {
        return $this->extensions;
    }
}
