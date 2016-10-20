<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Oro\Bundle\TranslationBundle\Extension\TranslationContextResolverInterface;

class TranslationContextProvider
{
    /** @var TranslationContextResolverInterface[] */
    protected $extensions = [];

    /** @var array */
    protected $context = [];

    /**
     * @param TranslationContextResolverInterface $extension
     * @param string $name
     */
    public function addExtension(TranslationContextResolverInterface $extension, $name)
    {
        $this->extensions[$name] = $extension;
    }

    /**
     * @param string $id
     * @return string|null
     */
    public function resolveContext($id)
    {
        if (!array_key_exists($id, $this->context)) {
            $this->context[$id] = null;

            if (!$this->extensions) {
                return null;
            }

            foreach ($this->extensions as $extension) {
                if (null !== ($context = $extension->resolve($id))) {
                    $this->context[$id] = $context;
                    break;
                }
            }
        }

        return $this->context[$id];
    }
}
