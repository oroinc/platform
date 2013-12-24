<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Soap;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapGetController;

class AddressTypeController extends SoapGetController
{
    /**
     * @Soap\Method("getAddressTypes")
     * @Soap\Result(phpType = "Oro\Bundle\AddressBundle\Entity\AddressTypeSoap[]")
     * @AclAncestor("oro_address_dictionaries_read")
     */
    public function cgetAction()
    {
        return $this->transformToSoapEntities($this->getRepository()->findAll());
    }

    /**
     * @Soap\Method("getAddressType")
     * @Soap\Param("name", phpType = "string")
     * @Soap\Result(phpType = "Oro\Bundle\AddressBundle\Entity\AddressTypeSoap")
     * @AclAncestor("oro_address_dictionaries_read")
     */
    public function getAction($name)
    {
        $entity = $this->getRepository()->find($name);

        if (!$entity) {
            throw new \SoapFault('NOT_FOUND', sprintf('Address type "%s" can\'t be found', $name));
        }

        return $this->transformToSoapEntity($entity);
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getManager()->getRepository('OroAddressBundle:AddressType');
    }

    /**
     * @return ObjectManager
     */
    public function getManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }
}
