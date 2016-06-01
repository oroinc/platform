<?php

/*
 * This file is part of the GenemuFormBundle package.
 *
 * (c) Olivier Chauvel <olivier@generation-multiple.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * {@inheritdoc}
 *
 * @author Bilal Amarni <bilal.amarni@gmail.com>
 */
class EmailAddressRecipientsTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($array)
    {
        if (null === $array || !is_array($array)) {
            return '';
        }

        return implode(';', $array);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($string)
    {
        if (is_array($string)) {
            return $string;
        }

        return explode(';', $string);
    }
}
