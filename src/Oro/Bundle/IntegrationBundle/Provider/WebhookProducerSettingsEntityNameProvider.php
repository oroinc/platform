<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides a text representation of WebhookProducerSettings entity.
 */
class WebhookProducerSettingsEntityNameProvider implements EntityNameProviderInterface
{
    private const TRANSLATION_KEY = 'oro.integration.webhookproducersettings.entity_name.label';

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof WebhookProducerSettings) {
            return false;
        }

        if (self::FULL === $format) {
            return $this->trans(
                self::TRANSLATION_KEY,
                [
                    '%topic%' => $entity->getTopic(),
                    '%notificationUrl%' => $entity->getNotificationUrl()
                ],
                $locale
            );
        }

        return false;
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, WebhookProducerSettings::class, true)) {
            return false;
        }

        if (self::FULL === $format) {
            $template = $this->trans(self::TRANSLATION_KEY, [], $locale);

            return sprintf('CONCAT(%s)', str_replace(
                ['%topic%', '%notificationUrl%'],
                [
                    sprintf("', %s.topic, '", $alias),
                    sprintf("', %s.notificationUrl, '", $alias)
                ],
                (string)(new Expr())->literal($template)
            ));
        }

        return false;
    }

    private function trans(string $key, array $params = [], string|Localization|null $locale = null): string
    {
        if ($locale instanceof Localization) {
            $locale = $locale->getLanguageCode();
        }

        return $this->translator->trans($key, $params, null, $locale);
    }
}
