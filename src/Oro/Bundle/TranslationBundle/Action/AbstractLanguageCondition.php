<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Action;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Base class for conditions that accept a single argument which is a language code, a language entity instance, or
 * a context property path pointing to a language code or a language entity instance.
 * If a language code is provided, it should be a code of a language that exists in the database.
 */
abstract class AbstractLanguageCondition extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private ManagerRegistry $doctrine;
    private ?string $languageCode = null;
    private ?PropertyPathInterface $languagePropertyPath = null;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @throws InvalidParameterException if the language argument is missing, or is not a language code and not
     *                                 a context property path, or if there is more that one argument provided.
     */
    public function initialize(array $options): self
    {
        if (1 !== \count($options)) {
            throw new InvalidArgumentException('The language argument value is required.');
        }

        $language = \reset($options);

        if ($language instanceof PropertyPathInterface) {
            $this->languagePropertyPath = $language;
        } else {
            $this->languageCode = $this->validateLanguageCode($language);
        }

        return $this;
    }

    /**
     * Gets the language from the options if it was specified in the options directly,
     * and if not - then from the specified context property path.
     *
     * @param mixed $context
     * @throws InvalidParameterException if the language code, or a context property path pointing to
     *                              the language code or a language entity instance were not provided.
     */
    protected function getLanguage($context): ?Language
    {
        $languageCode = null;
        if (null !== $this->languagePropertyPath) {
            $language = $this->contextAccessor->getValue($context, $this->languagePropertyPath);
            if ($language instanceof Language) {
                return $language;
            }
            $languageCode = $this->validateLanguageCode($language);
        }
        $languageCode = $languageCode ?? $this->languageCode;

        if (null === $languageCode) {
            return null;
        }

        $languageRepository = $this->doctrine->getRepository(Language::class);

        return $languageRepository->findOneBy(['code' => $languageCode]);
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
