<?php

namespace Oro\Bundle\LayoutBundle\Layout\Serializer;

use Oro\Component\Layout\Util\BlockUtils;

/**
 * The base implementation of normalizer for "vars" section of layout block view.
 */
class BlockViewVarsNormalizer implements BlockViewVarsNormalizerInterface
{
    private const CONTEXT_HASH = 'context_hash';

    private const VISIBLE = 'visible';
    private const HIDDEN = 'hidden';
    private const ATTR = 'attr';
    private const TRANSLATION_DOMAIN = 'translation_domain';
    private const MESSAGES = 'messages';
    private const CLASS_PREFIX = 'class_prefix';
    private const BLOCK_TYPE_WIDGET_ID = 'block_type_widget_id';
    private const UNIQUE_BLOCK_PREFIX = 'unique_block_prefix';
    private const CACHE_KEY = 'cache_key';
    private const CACHE = 'cache';

    /**
     * {@inheritDoc}
     */
    public function normalize(array &$vars, array $context): void
    {
        $this->unsetDefaults($vars);
        $this->unsetComputed($vars);

        if (\array_key_exists(self::CACHE, $vars) && null === $vars[self::CACHE]) {
            unset($vars[self::CACHE]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize(array &$vars, array $context): void
    {
        $this->populateDefaults($vars);
        $this->populateComputed($vars, $context[self::CONTEXT_HASH]);

        if (!\array_key_exists(self::CACHE, $vars)) {
            $vars[self::CACHE] = null;
        }
    }

    protected function unsetDefaults(array &$vars): void
    {
        if (\array_key_exists(self::VISIBLE, $vars) && $vars[self::VISIBLE] === true) {
            unset($vars[self::VISIBLE]);
        }
        if (\array_key_exists(self::HIDDEN, $vars) && $vars[self::HIDDEN] === false) {
            unset($vars[self::HIDDEN]);
        }
        if (\array_key_exists(self::ATTR, $vars) && empty($vars[self::ATTR])) {
            unset($vars[self::ATTR]);
        }
        if (\array_key_exists(self::TRANSLATION_DOMAIN, $vars) && $vars[self::TRANSLATION_DOMAIN] === self::MESSAGES) {
            unset($vars[self::TRANSLATION_DOMAIN]);
        }
    }

    protected function populateDefaults(array &$vars): void
    {
        if (!\array_key_exists(self::VISIBLE, $vars)) {
            $vars[self::VISIBLE] = true;
        }
        if (!\array_key_exists(self::HIDDEN, $vars)) {
            $vars[self::HIDDEN] = false;
        }
        if (!\array_key_exists(self::ATTR, $vars)) {
            $vars[self::ATTR] = [];
        }
        if (!\array_key_exists(self::TRANSLATION_DOMAIN, $vars)) {
            $vars[self::TRANSLATION_DOMAIN] = self::MESSAGES;
        }
    }

    protected function unsetComputed(array &$vars): void
    {
        if (\array_key_exists(self::CLASS_PREFIX, $vars) && null === $vars[self::CLASS_PREFIX]) {
            unset($vars[self::CLASS_PREFIX]);
        }
        unset(
            $vars[self::BLOCK_TYPE_WIDGET_ID],
            $vars[self::UNIQUE_BLOCK_PREFIX],
            $vars[self::CACHE_KEY]
        );
    }

    protected function populateComputed(array &$vars, string $contextHash): void
    {
        if (!\array_key_exists(self::CLASS_PREFIX, $vars)) {
            $vars[self::CLASS_PREFIX] = null;
        }
        BlockUtils::populateComputedViewVars($vars, $contextHash);
    }
}
