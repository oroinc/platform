<?php

namespace Oro\Bundle\SoapBundle\ServiceDefinition\Loader;

use BeSimple\SoapBundle\ServiceDefinition\ComplexType;
use BeSimple\SoapBundle\Util\Collection;

use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class SoapAclFieldFilter implements ComplexTypeFilterInterface
{
    /** @var AuthorizationChecker */
    protected $securityChecker;

    public function __construct(AuthorizationChecker $authorizationChecker)
    {
        $this->securityChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function filterProperties($className, Collection $properties)
    {
        $newProperties = clone $properties;
        $newProperties->clear();

        // Check if it's *Soap class and check against it's parent (e.g. BusinessUnitSoap and BusinessUnit)
        $sampleObject = $this->getObjectToCheck($className);

        /** @var ComplexType $property */
        foreach ($properties as $property) {
            $fieldName = $property->getName();

            if ($this->securityChecker->isGranted(['VIEW', 'EDIT'], new FieldVote($sampleObject, $fieldName))) {
                $newProperties->add($property);
            }
        }

        return $newProperties;
    }

    /**
     * @param string $className
     *
     * @return object
     */
    protected function getObjectToCheck($className)
    {
        $reflectionClass = new \ReflectionClass($className);
        if (substr($className, -4) == 'Soap') {
            $reflectionClass = $reflectionClass->getParentClass() ?: $reflectionClass;
        }

        return $reflectionClass->newInstanceWithoutConstructor();
    }
}
