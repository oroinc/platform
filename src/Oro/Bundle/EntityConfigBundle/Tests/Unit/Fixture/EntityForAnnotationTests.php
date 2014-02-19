<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @Config(
 *      routeName="test_route_name",
 *      routeView="test_route_view",
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id"
 *          }
 *      }
 * )
 */
class EntityForAnnotationTests
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     *
     * @ConfigField(
     *  defaultValues={
     *      "email"={"available_in_template"=true}
     *  }
     * )
     */
    protected $name;
}
