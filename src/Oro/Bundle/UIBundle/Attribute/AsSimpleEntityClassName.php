<?php

declare(strict_types=1);

namespace Oro\Bundle\UIBundle\Attribute;

/**
 * Signals `EntityLabelBuilder` to apply simpler class name transformation rules:
 * - does not remove `Entity`/`Model`/`Document` from the class name, only removes `Bundle`
 * - combines vendor and bundle names into a single part: `Acme\Bundle\ProductBundle` -> `acme_product`
 * - converts `CamelCase` to `snake_case`
 *
 * Examples of produced paths for translation keys:
 * - `App\Entity\Product` -> `app.entity.product`
 * - `App\Model\Product` -> `app.model.product`
 * - `App\Document\Product` -> `app.document.product`
 * - `App\Document\Dir\SomeProduct` -> `app.document.dir.some_product`
 * - `Acme\Bundle\ProductBundle\Entity\SomeProduct` -> `acme_product.entity.some_product`
 * - `Acme\Bundle\ProductBundle\Model\SomeProduct` -> `acme_product.model.some_product`
 * - `Acme\Bundle\ProductBundle\Document\SomeProduct` -> `acme_product.document.some_product`
 * - `Acme\Bundle\ProductBundle\OtherDir\SomeProduct` -> `acme_product.other_dir.some_product`
 * @see \Oro\Bundle\UIBundle\Tools\EntityLabelBuilder::explodeClassName()
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsSimpleEntityClassName
{
}
