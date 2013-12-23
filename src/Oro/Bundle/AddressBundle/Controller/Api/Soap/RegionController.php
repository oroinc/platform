<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Soap;

use Doctrine\Common\Persistence\ObjectManager;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapGetController;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;

class RegionController extends SoapGetController
{
    /**
     * @Soap\Method("getRegions")
     * @Soap\Result(phpType = "Oro\Bundle\AddressBundle\Entity\RegionSoap[]")
     * @AclAncestor("oro_address_dictionaries_read")
     */
    public function cgetAction()
    {
        return $this->transformToSoapEntities($this->getRepository()->findAll());
    }

    /**
     * @Soap\Method("getRegion")
     * @Soap\Param("combinedCode", phpType = "string")
     * @Soap\Result(phpType = "Oro\Bundle\AddressBundle\Entity\RegionSoap")
     * @AclAncestor("oro_address_dictionaries_read")
     */
    public function getAction($combinedCode)
    {
        $entity = $this->getRepository()->find($combinedCode);

        if (!$entity) {
            throw new \SoapFault('NOT_FOUND', sprintf('Region with code "%s" can not be found', $combinedCode));
        }

        return $this->transformToSoapEntity($entity);
    }

    /**
     * @Soap\Method("getRegionByCountry")
     * @Soap\Param("countryIso2Code", phpType = "string")
     * @Soap\Result(phpType = "Oro\Bundle\AddressBundle\Entity\RegionSoap[]")
     * @AclAncestor("oro_address_dictionaries_read")
     */
    public function getByCountryAction($countryIso2Code)
    {
        $country = $this->getManager()->getRepository('OroAddressBundle:Country')->find($countryIso2Code);

        if (!$country) {
            throw new \SoapFault('NOT_FOUND', sprintf('Country with code "%s" can not be found', $countryIso2Code));
        }

        return $this->transformToSoapEntities($this->getRepository()->getCountryRegions($country));
    }

    /**
     * @return RegionRepository
     */
    protected function getRepository()
    {
        return $this->getManager()->getRepository('OroAddressBundle:Region');
    }

    /**
     * @return ObjectManager
     */
    public function getManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }
}
