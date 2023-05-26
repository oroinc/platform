<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Resolver\ResolverInterface;

trait ResolveTranslationIdTrait
{
    private function resolveTranslationId(string $id, ?ResolverInterface $resolver = null): string
    {
        if (preg_match_all('/<@[\w\-]+\->[\w]+>/', $id, $matches)) {
            if (null === $resolver) {
                $resolver = self::getReferenceResolver();
            }
            foreach ($matches[0] as $val) {
                $resolvedVal = $resolver->resolve('<toString(' . substr($val, 1, -1) . ')>');
                $id = str_replace($val, $resolvedVal, $id);
            }
        }

        return $id;
    }
}
