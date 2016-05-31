<?php

namespace Oro\Bundle\EntityExtendBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumValueRepository extends EntityRepository
{
    /**
     * Creates an entity represents an enum value
     *
     * @param string      $name     The enum value name
     * @param int         $priority An number used to sort enum values on UI.
     *                              Values with less priority is rendered at the top
     * @param boolean     $default  Determines if this value is selected by default for new records
     * @param string|null $id       The enum value identifier. If not specified it is generated
     *                              automatically based on the given name. Usually it is the same as name,
     *                              but spaces are replaced with underscore and result is converted to lower case.
     *                              As the id length is limited to 32 characters, in case if the name is longer then
     *                              some hashing function is used to generate the id.
     *
     * @return AbstractEnumValue
     *
     * @throws \InvalidArgumentException
     */
    public function createEnumValue($name, $priority, $default, $id = null)
    {
        if (strlen($name) === 0) {
            throw new \InvalidArgumentException('$name must not be empty.');
        }
        if (!isset($id) || $id === '') {
            $id = ExtendHelper::buildEnumValueId($name);
        } elseif (strlen($id) > ExtendHelper::MAX_ENUM_VALUE_ID_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    '$id length must be less or equal %d characters. id: %s.',
                    ExtendHelper::MAX_ENUM_VALUE_ID_LENGTH,
                    $id
                )
            );
        }

        $enumValueClassName = $this->getClassName();

        return new $enumValueClassName($id, $name, $priority, $default);
    }

    /**
     * @return QueryBuilder
     */
    public function getValuesQueryBuilder()
    {
        $qb = $this->createQueryBuilder('e');
        $qb->orderBy($qb->expr()->asc('e.priority'));

        return $qb;
    }

    /**
     * @return AbstractEnumValue[]
     */
    public function getValues()
    {
        return $this->getValuesQueryBuilder()->getQuery()->getResult();
    }

    /**
     * @return QueryBuilder
     */
    public function getDefaultValuesQueryBuilder()
    {
        $qb = $this->getValuesQueryBuilder();
        $qb->andWhere($qb->expr()->eq('e.default', ':default'))
            ->setParameter('default', true);

        return $qb;
    }

    /**
     * @return AbstractEnumValue[]
     */
    public function getDefaultValues()
    {
        return $this->getDefaultValuesQueryBuilder()->getQuery()->getResult();
    }
}
