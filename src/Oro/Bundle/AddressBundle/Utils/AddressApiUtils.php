<?php

namespace Oro\Bundle\AddressBundle\Utils;

use Doctrine\ORM\EntityManager;

/**
 * @todo: it is temporary workaround to avoid copy-paste until new API is implemented
 */
class AddressApiUtils
{
    /**
     * @param bool $isTypedAddress
     *
     * @return array
     */
    public static function getAddressConfig($isTypedAddress = false)
    {
        $result = [
            'excluded_fields' => ['owner'],
            'fields'          => [
                'country' => ['fields' => 'name'],
                'region'  => ['fields' => 'name']
            ],
            'post_serialize'  => function (array &$result) {
                self::postSerializeAddress($result);
            }
        ];

        if ($isTypedAddress) {
            $result['fields']['types'] = [
                'fields'  => 'name',
                'orderBy' => [
                    'name' => 'ASC'
                ]
            ];
        }

        return $result;
    }

    /**
     * @param array         $address
     * @param EntityManager $em
     */
    public static function fixAddress(array &$address, EntityManager $em)
    {
        // @todo: just a temporary workaround until new API is implemented
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

    /**
     * @param string        $countryName
     * @param EntityManager $em
     *
     * @return string|null
     */
    protected static function getCountryCodeByName($countryName, EntityManager $em)
    {
        $countryRepo = $em->getRepository('OroAddressBundle:Country');
        $country     = $countryRepo->createQueryBuilder('c')
            ->select('c.iso2Code')
            ->where('c.name = :name')
            ->setParameter('name', $countryName)
            ->getQuery()
            ->getArrayResult();

        return !empty($country) ? $country[0]['iso2Code'] : null;
    }

    /**
     * @param string        $region
     * @param EntityManager $em
     *
     * @return bool
     */
    protected static function isRegionCombinedCodeByCode($region, EntityManager $em)
    {
        $regionRepo = $em->getRepository('OroAddressBundle:Region');
        $region     = $regionRepo->createQueryBuilder('r')
            ->select('r.combinedCode')
            ->where('r.combinedCode = :region')
            ->setParameter('region', $region)
            ->getQuery()
            ->getArrayResult();

        return !empty($region);
    }

    /**
     * @param string        $countryCode
     * @param string        $regionCode
     * @param EntityManager $em
     *
     * @return string|null
     */
    protected static function getRegionCombinedCodeByCode($countryCode, $regionCode, EntityManager $em)
    {
        $regionRepo = $em->getRepository('OroAddressBundle:Region');
        $region     = $regionRepo->createQueryBuilder('r')
            ->select('r.combinedCode')
            ->innerJoin('r.country', 'c')
            ->where('c.iso2Code = :country AND r.code = :region')
            ->setParameter('country', $countryCode)
            ->setParameter('region', $regionCode)
            ->getQuery()
            ->getArrayResult();

        return !empty($region) ? $region[0]['combinedCode'] : null;
    }

    /**
     * @param string        $countryCode
     * @param string        $regionName
     * @param EntityManager $em
     *
     * @return string|null
     */
    protected static function getRegionCombinedCodeByName($countryCode, $regionName, EntityManager $em)
    {
        $regionRepo = $em->getRepository('OroAddressBundle:Region');
        $region     = $regionRepo->createQueryBuilder('r')
            ->select('r.combinedCode')
            ->innerJoin('r.country', 'c')
            ->where('c.iso2Code = :country AND r.name = :region')
            ->setParameter('country', $countryCode)
            ->setParameter('region', $regionName)
            ->getQuery()
            ->getArrayResult();

        return !empty($region) ? $region[0]['combinedCode'] : null;
    }

    /**
     * @param array $result
     */
    protected static function postSerializeAddress(array &$result)
    {
        // @todo: just a temporary workaround until new API is implemented
        // the normal solution can be to use region_name virtual field and
        // exclusion rule declared in oro/entity.yml
        // - for 'region' field use a region text if filled; otherwise, use region name
        // - remove regionText field from a result
        if (!empty($result['regionText'])) {
            $result['region'] = $result['regionText'];
        }
        unset($result['regionText']);
    }
}
