<?php

namespace Oro\Bundle\MigrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("oro_migrations", indexes={
 *     @ORM\Index(name="idx_oro_migrations", columns={"bundle"})
 * })
 * @ORM\Entity()
 */
class DataMigration
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="bundle", type="string", length=250)
     */
    protected $bundle;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=250)
     */
    protected $version;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="loaded_at", type="datetime")
     */
    protected $loadedAt;
}
