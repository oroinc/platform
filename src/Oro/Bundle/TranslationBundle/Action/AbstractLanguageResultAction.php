<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Action;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Base class for actions that accept exactly two parameters: "language" and "result".
 */
abstract class AbstractLanguageResultAction extends AbstractAction
{
    private ?string $languageCode = null;
    private ?PropertyPathInterface $languagePropertyPath = null;
    protected ?PropertyPathInterface $resultPropertyPath = null;

    /**
     * @throws InvalidParameterException if the provided options are not correct
     */
    public function initialize(array $options): self
    {
        if (2 !== \count($options)) {
            throw new InvalidParameterException('Both "language" and "result" parameters are required.');
        }

        $language = $options['language'] ?? null;
        $result = $options['result'] ?? null;

        if (null === $language) {
            throw new InvalidParameterException('The "language" parameter value is required.');
        }

        if ($language instanceof PropertyPathInterface) {
            $this->languagePropertyPath = $language;
        } else {
            $this->languageCode = $this->validateLanguageCode($language);
        }

        if (!($result instanceof PropertyPathInterface)) {
            throw new InvalidParameterException('The "result" parameter must be a valid property definition.');
        }
        $this->resultPropertyPath = $result;

        return $this;
    }

    /**
     * Gets the language code from the options if it was specified in the options directly,
     * and if not - then from the specified context property path.
     *
     * @param mixed $context
     * @throws InvalidParameterException if the language code, or a language entity instance or a context property path
     *                                   pointing to the language code or a language entity instance were not provided.
     */
    protected function getLanguageCode($context): string
    {
        return $this->validateLanguageCode(
            $this->languageCode ?? $this->contextAccessor->getValue($context, $this->languagePropertyPath)
        );
    }

    /**
     * @param Language|string $language
     * @return string
     */
    private function validateLanguageCode($language): string
    {
        if (\is_string($language) && '' !== $language) {
            $languageCode = $language;
        } elseif ($language instanceof Language) {
            $languageCode = $language->getCode();
        } else {
            throw new InvalidParameterException(
                'Language should be a non-empty string or a language entity instance.'
            );
        }
        return $languageCode;
    }
}
