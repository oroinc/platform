<?php

namespace Oro\Bundle\AddressBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

/**
 * Contains a set of static methods that are used in API managers and controllers for different kind of addresses.
 * It is temporary workaround to avoid copy-paste until new API is implemented
 */
class AddressApiUtils
{
    public static function getAddressConfig(bool $isTypedAddress = false): array
    {
        $result = [
            'fields' => [
                'owner'   => ['exclude' => true],
                'country' => ['fields' => 'name'],
                'region'  => ['fields' => 'name']
            ],
            'post_serialize'  => function (array $result) {
                return self::postSerializeAddress($result);
            }
        ];

        if ($isTypedAddress) {
            $result['fields']['types'] = [
                'fields'   => 'name',
                'order_by' => [
                    'name' => 'ASC'
                ]
            ];
        }

        return $result;
    }

    public static function fixAddress(array &$address, EntityManagerInterface $em): void
    {
        // just a temporary workaround until new API is implemented
        // - convert country name to country code (as result we accept both the code and the name)
        //   also it will be good to accept ISO3 code in future, need to be discussed with product owners
        // - convert region name to region code (as result we accept the combined code, code and name)
        // - move region name to region_text field for unknown region
        if (!empty($address['country'])) {
            $countryCode = self::getCountryCodeByName($address['country'], $em);
            if (!empty($countryCode)) {
                $address['country'] = $countryCode;
            }
        }
        if (!empty($address['region']) && !self::isRegionCombinedCodeByCode($address['region'], $em)) {
            if (!empty($address['country'])) {
                $regionId = self::getRegionCombinedCodeByCode($address['country'], $address['region'], $em);
                if (!empty($regionId)) {
                    $address['region'] = $regionId;
                } else {
                    $regionId = self::getRegionCombinedCodeByName($address['country'], $address['region'], $em);
                    if (!empty($regionId)) {
                        $address['region'] = $regionId;
                    } else {
                        $address['region_text'] = $address['region'];
                        unset($address['region']);
                    }
                }
            } else {
                $address['region_text'] = $address['region'];
                unset($address['region']);
            }
        }
    }

    protected static function getCountryCodeByName(string $countryName, EntityManagerInterface $em): ?string
    {
        $country = $em->getRepository(Country::class)->createQueryBuilder('c')
            ->select('c.iso2Code')
            ->where('c.name = :name')
            ->setParameter('name', $countryName)
            ->getQuery()
            ->getArrayResult();

        return !empty($country) ? $country[0]['iso2Code'] : null;
    }

    protected static function isRegionCombinedCodeByCode(string $regionCode, EntityManagerInterface $em): bool
    {
        $region = $em->getRepository(Region::class)->createQueryBuilder('r')
            ->select('r.combinedCode')
            ->where('r.combinedCode = :region')
            ->setParameter('region', $regionCode)
            ->getQuery()
            ->getArrayResult();

        return !empty($region);
    }

    protected static function getRegionCombinedCodeByCode(
        string $countryCode,
        string $regionCode,
        EntityManagerInterface $em
    ): ?string {
        $region = $em->getRepository(Region::class)->createQueryBuilder('r')
            ->select('r.combinedCode')
            ->innerJoin('r.country', 'c')
            ->where('c.iso2Code = :country AND r.code = :region')
            ->setParameter('country', $countryCode)
            ->setParameter('region', $regionCode)
            ->getQuery()
            ->getArrayResult();

        return !empty($region) ? $region[0]['combinedCode'] : null;
    }

    protected static function getRegionCombinedCodeByName(
        string $countryCode,
        string $regionName,
        EntityManagerInterface $em
    ): ?string {
        $region = $em->getRepository(Region::class)->createQueryBuilder('r')
            ->select('r.combinedCode')
            ->innerJoin('r.country', 'c')
            ->where('c.iso2Code = :country AND r.name = :region')
            ->setParameter('country', $countryCode)
            ->setParameter('region', $regionName)
            ->getQuery()
            ->getArrayResult();

        return !empty($region) ? $region[0]['combinedCode'] : null;
    }

    protected static function postSerializeAddress(array $result): array
    {
        // just a temporary workaround until new API is implemented
        // the normal solution can be to use region_name virtual field and
        // exclusion rule declared in oro/entity.yml
        // - for 'region' field use a region text if filled; otherwise, use region name
        // - remove regionText field from a result
        if (!empty($result['regionText'])) {
            $result['region'] = $result['regionText'];
        }
        unset($result['regionText']);

        return $result;
    }
}
