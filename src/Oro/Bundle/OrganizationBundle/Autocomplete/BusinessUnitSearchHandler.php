<?php

namespace Oro\Bundle\OrganizationBundle\Autocomplete;


use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;

class BusinessUnitSearchHandler extends SearchHandler
{
    
}
