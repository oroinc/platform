<?php

namespace Oro\Bundle\TagBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\UserBundle\Entity\User;

class TaxonomyRepository extends EntityRepository
{

}
