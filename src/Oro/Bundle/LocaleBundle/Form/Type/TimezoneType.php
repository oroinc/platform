<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The form type that can be used to select timezone.
 */
class TimezoneType extends AbstractType
{
    protected static ?array $timezones = null;
    protected ?CacheInterface $cache;

    public function __construct(CacheInterface $cache = null)
    {
        $this->cache = $cache;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        if ($this->cache) {
            self::$timezones = $this->cache->get('timezones', function () {
                return self::getTimezones();
            });
        }

        $resolver->setDefaults(
            array(
                'choices' => self::$timezones ? array_flip(self::$timezones) : null,
            )
        );
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return 'oro_locale_timezone';
    }

    /**
     * Returns the timezone choices.
     *
     * The choices are generated from the ICU function
     * \DateTimeZone::listIdentifiers(). They are cached during a single request,
     * so multiple timezone fields on the same page don't lead to unnecessary
     * overhead.
     */
    public static function getTimezones(): array
    {
        if (null === static::$timezones) {
            static::$timezones = array();

            $timezones = self::getTimezonesData();
            foreach ($timezones as $timezoneData) {
                $timezone = $timezoneData['timezone_id'];
                $offset = $timezoneData['offset'];
                $parts = explode('/', $timezone);

                if (count($parts) > 2) {
                    $region = $parts[0];
                    $name = $parts[1].' - '.$parts[2];
                } elseif (count($parts) > 1) {
                    $region = $parts[0];
                    $name = $parts[1];
                } else {
                    $region = 'Other';
                    $name = $parts[0];
                }

                $timezoneOffset = sprintf(
                    'UTC %+03d:%02u',
                    $offset / 3600,
                    abs($offset) % 3600 / 60
                );
                $timezoneName = '(' . $timezoneOffset . ') ';
                if ($region) {
                    $timezoneName .= $region . '/';
                }
                $timezoneName .= $name;
                static::$timezones[$timezone] = str_replace('_', ' ', $timezoneName);
            }
        }

        return static::$timezones;
    }

    /**
     * Get timezone identifiers with offset sorted by offset and timezone_id.
     */
    public static function getTimezonesData(): array
    {
        $listIdentifiers = \DateTimeZone::listIdentifiers();
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $timezones = array();
        foreach ($listIdentifiers as $identifier) {
            $timezone = new \DateTimeZone($identifier);
            $timezones[$identifier] = array(
                'offset' => $timezone->getOffset($now),
                'timezone_id' => $identifier
            );
        }

        usort(
            $timezones,
            function ($a, $b) {
                if ($a['offset'] == $b['offset']) {
                    return strcmp($a['timezone_id'], $b['timezone_id']);
                }
                return $a['offset'] <=> $b['offset'];
            }
        );
        return $timezones;
    }
}
