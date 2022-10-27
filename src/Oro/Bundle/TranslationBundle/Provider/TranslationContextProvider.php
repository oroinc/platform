<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Oro\Bundle\TranslationBundle\Extension\TranslationContextResolverInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Resolves a translation context using configured resolvers.
 */
class TranslationContextProvider implements ResetInterface
{
    /** @var iterable|TranslationContextResolverInterface[] */
    private $extensions;

    /** @var array */
    private $context = [];

    /**
     * @param iterable|TranslationContextResolverInterface[] $extensions
     */
    public function __construct(iterable $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * @param string $id
     *
     * @return string|null
     */
    public function resolveContext($id)
    {
        if (!array_key_exists($id, $this->context)) {
            $this->context[$id] = null;
            foreach ($this->extensions as $extension) {
                $context = $extension->resolve($id);
                if (null !== $context) {
                    $this->context[$id] = $context;
                    break;
                }
            }
        }

        return $this->context[$id];
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->context = [];
    }
}
