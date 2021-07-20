<?php

namespace Oro\Bundle\TranslationBundle\Action;

use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheHandlerInterface;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Rebuilds the translation cache.
 */
class RebuildTranslationCacheAction extends AbstractAction
{
    /** @var RebuildTranslationCacheHandlerInterface */
    private $rebuildTranslationCacheHandler;

    /** @var TranslatorInterface */
    private $translator;

    /** @var PropertyPathInterface */
    private $attribute;

    public function __construct(
        ContextAccessor $contextAccessor,
        RebuildTranslationCacheHandlerInterface $rebuildTranslationCacheHandler,
        TranslatorInterface $translator
    ) {
        parent::__construct($contextAccessor);
        $this->rebuildTranslationCacheHandler = $rebuildTranslationCacheHandler;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    protected function executeAction($context)
    {
        $result = $this->rebuildTranslationCacheHandler->rebuildCache();

        $attributeValue = ['successful' => $result->isSuccessful(), 'message' => null];
        if (!$result->isSuccessful()) {
            $attributeValue['message'] = $result->getFailureMessage() ?: $this->getDefaultFailureMessage();
        }

        $this->contextAccessor->setValue($context, $this->attribute, $attributeValue);
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        $attribute = $options['attribute'] ?? null;
        if (!$attribute) {
            throw new InvalidParameterException('Parameter "attribute" is required.');
        }
        if (!$attribute instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Parameter "attribute" must be valid property definition.');
        }

        $this->attribute = $attribute;

        return $this;
    }

    private function getDefaultFailureMessage(): string
    {
        return $this->translator->trans('oro.translation.translation.message.rebuild_cache_failure');
    }
}
