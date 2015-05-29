<?php
namespace Oro\Bundle\LDAPBundle\LDAP;

trait TransformsSettings
{
    /**
     * Transforms settings according to provided transforms.
     *
     * @param array|\Traversable $settings
     * @param array $transforms
     *
     * @return array Array of transformed settings.
     */
    protected function transformSettings($settings, array $transforms)
    {
        if ($settings instanceof \Traversable) {
            $settings = iterator_to_array($settings);
        }

        $transformed = [];

        foreach ($settings as $settingKey => $settingValue) {
            if (!isset($transforms[$settingKey])) {
                continue;
            }

            if (is_callable($transforms[$settingKey])) {
                $transformed = array_merge(
                    $transformed,
                    call_user_func($transforms[$settingKey], $settingValue)
                );
            } else {
                $transformed[$transforms[$settingKey]] = $settingValue;
            }
        }

        return $transformed;
    }
}
