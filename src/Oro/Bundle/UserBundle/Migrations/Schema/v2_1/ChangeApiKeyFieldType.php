<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Converts api_key column of oro_user_api table to crypted_string type and converts data.
 */
class ChangeApiKeyFieldType implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_user_api');
        if ($table->getColumn('api_key')->getType()->getName() !== 'crypted_string') {
            $type = Type::getType('crypted_string');
            $table->changeColumn(
                'api_key',
                ['type' => $type, 'comment' => '(DC2Type:crypted_string)']
            );
            $queries->addPostQuery(
                new ConvertApiKeyDataFormatQuery($this->container->get('oro_security.encoder.repetitive_crypter'))
            );
        }
    }
}
