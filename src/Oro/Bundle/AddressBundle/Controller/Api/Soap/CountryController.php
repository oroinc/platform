<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Soap;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapGetController;
use Oro\Bundle\AddressBundle\Entity\Address;

class CountryController extends SoapGetController
{
    /**
     * @Soap\Method("getCountries")
     * @Soap\Result(phpType = "Oro\Bundle\AddressBundle\Entity\CountrySoap[]")
     * @AclAncestor("oro_address_dictionaries_read")
     */
    public function cgetAction()
    {
        return $this->transformToSoapEntities($this->getRepository()->findAll());
    }

    /**
     * @Soap\Method("getCountry")
     * @Soap\Param("iso2Code", phpType = "string")
     * @Soap\Result(phpType = "Oro\Bundle\AddressBundle\Entity\CountrySoap")
     * @AclAncestor("oro_address_dictionaries_read")
     */
    public function getAction($iso2Code)
    {
        return $this->transformToSoapEntity($this->getEntity($iso2Code));
    }

    /**
     * Shortcut to get entity
     *
     * @param int|string $id
     * @throws \SoapFault
     * @return Address
     */
    protected function getEntity($id)
    {
        $entity = $this->getRepository()->find($id);

        if (!$entity) {
            throw new \SoapFault('NOT_FOUND', sprintf('Record #%u can not be found', $id));
        }

        return $entity;
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getManager()->getRepository('OroAddressBundle:Country');
    }

    /**
     * @return ObjectManager
     */
    public function getManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }
}
