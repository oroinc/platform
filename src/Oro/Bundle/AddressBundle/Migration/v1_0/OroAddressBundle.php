<?php

namespace Oro\Bundle\AddressBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroAddressBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_address (id INT AUTO_INCREMENT NOT NULL, region_code VARCHAR(16) DEFAULT NULL, country_code VARCHAR(2) DEFAULT NULL, `label` VARCHAR(255) DEFAULT NULL, street VARCHAR(500) NOT NULL, street2 VARCHAR(500) DEFAULT NULL, city VARCHAR(255) NOT NULL, postal_code VARCHAR(20) NOT NULL, organization VARCHAR(255) DEFAULT NULL, region_text VARCHAR(255) DEFAULT NULL, name_prefix VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, middle_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, name_suffix VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_C5E99957F026BB7C (country_code), INDEX IDX_C5E99957AEB327AF (region_code), PRIMARY KEY(id))",
            "CREATE TABLE oro_address_type (name VARCHAR(16) NOT NULL, `label` VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8B3E52E3EA750E8 (`label`), PRIMARY KEY(name))",
            "CREATE TABLE oro_address_type_translation (id INT AUTO_INCREMENT NOT NULL, foreign_key VARCHAR(16) NOT NULL, content VARCHAR(255) NOT NULL, locale VARCHAR(8) NOT NULL, object_class VARCHAR(255) NOT NULL, field VARCHAR(32) NOT NULL, INDEX address_type_translation_idx (locale, object_class, field, foreign_key), PRIMARY KEY(id))",
            "CREATE TABLE oro_dictionary_country (iso2_code VARCHAR(2) NOT NULL, iso3_code VARCHAR(3) NOT NULL, name VARCHAR(255) NOT NULL, INDEX country_name_idx (name), PRIMARY KEY(iso2_code));",
            "CREATE TABLE oro_dictionary_country_translation (id INT AUTO_INCREMENT NOT NULL, foreign_key VARCHAR(2) NOT NULL, content VARCHAR(255) NOT NULL, locale VARCHAR(8) NOT NULL, object_class VARCHAR(255) NOT NULL, field VARCHAR(32) NOT NULL, INDEX country_translation_idx (locale, object_class, field, foreign_key), PRIMARY KEY(id));",
            "CREATE TABLE oro_dictionary_region (combined_code VARCHAR(16) NOT NULL, country_code VARCHAR(2) DEFAULT NULL, code VARCHAR(32) NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_8C71325AF026BB7C (country_code), INDEX region_name_idx (name), PRIMARY KEY(combined_code));",
            "CREATE TABLE oro_dictionary_region_translation (id INT AUTO_INCREMENT NOT NULL, foreign_key VARCHAR(16) NOT NULL, content VARCHAR(255) NOT NULL, locale VARCHAR(8) NOT NULL, object_class VARCHAR(255) NOT NULL, field VARCHAR(32) NOT NULL, INDEX region_translation_idx (locale, object_class, field, foreign_key), PRIMARY KEY(id));",

            "ALTER TABLE oro_address ADD CONSTRAINT FK_C5E99957AEB327AF FOREIGN KEY (region_code) REFERENCES oro_dictionary_region (combined_code)",
            "ALTER TABLE oro_address ADD CONSTRAINT FK_C5E99957F026BB7C FOREIGN KEY (country_code) REFERENCES oro_dictionary_country (iso2_code)",
            "ALTER TABLE oro_dictionary_region ADD CONSTRAINT FK_8C71325AF026BB7C FOREIGN KEY (country_code) REFERENCES oro_dictionary_country (iso2_code)"
        ];
    }
}
