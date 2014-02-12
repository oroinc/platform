<?php

namespace Oro\Bundle\UserBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroUserBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_access_group (id SMALLINT AUTO_INCREMENT NOT NULL, business_unit_owner_id INT DEFAULT NULL, name VARCHAR(30) NOT NULL, UNIQUE INDEX UNIQ_FEF9EDB75E237E06 (name), INDEX IDX_FEF9EDB759294170 (business_unit_owner_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_access_role (id SMALLINT AUTO_INCREMENT NOT NULL, business_unit_owner_id INT DEFAULT NULL, role VARCHAR(30) NOT NULL, `label` VARCHAR(30) NOT NULL, UNIQUE INDEX UNIQ_673F65E757698A6A (role), INDEX IDX_673F65E759294170 (business_unit_owner_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_session (id VARCHAR(255) NOT NULL, sess_data LONGTEXT NOT NULL, sess_time INT NOT NULL, PRIMARY KEY(id))",
            "CREATE TABLE oro_user (id INT AUTO_INCREMENT NOT NULL, imap_configuration_id INT DEFAULT NULL, business_unit_owner_id INT DEFAULT NULL, status_id SMALLINT DEFAULT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, name_prefix VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, middle_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, name_suffix VARCHAR(255) DEFAULT NULL, birthday DATE DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, enabled TINYINT(1) NOT NULL, salt VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, confirmation_token VARCHAR(255) DEFAULT NULL, password_requested DATETIME DEFAULT NULL, last_login DATETIME DEFAULT NULL, login_count INT UNSIGNED DEFAULT 0 NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, UNIQUE INDEX UNIQ_F82840BCF85E0677 (username), UNIQUE INDEX UNIQ_F82840BCE7927C74 (email), UNIQUE INDEX UNIQ_F82840BC6BF700BD (status_id), UNIQUE INDEX UNIQ_F82840BC678BF607 (imap_configuration_id), INDEX IDX_F82840BC59294170 (business_unit_owner_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_user_access_group (user_id INT NOT NULL, group_id SMALLINT NOT NULL, INDEX IDX_EC003EF3A76ED395 (user_id), INDEX IDX_EC003EF3FE54D947 (group_id), PRIMARY KEY(user_id, group_id))",
            "CREATE TABLE oro_user_access_group_role (group_id SMALLINT NOT NULL, role_id SMALLINT NOT NULL, INDEX IDX_E7E7E38EFE54D947 (group_id), INDEX IDX_E7E7E38ED60322AC (role_id), PRIMARY KEY(group_id, role_id))",
            "CREATE TABLE oro_user_access_role (user_id INT NOT NULL, role_id SMALLINT NOT NULL, INDEX IDX_290571BEA76ED395 (user_id), INDEX IDX_290571BED60322AC (role_id), PRIMARY KEY(user_id, role_id))",
            "CREATE TABLE oro_user_api (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, api_key VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_296B6993C912ED9D (api_key), UNIQUE INDEX UNIQ_296B6993A76ED395 (user_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_user_business_unit (user_id INT NOT NULL, business_unit_id INT NOT NULL, INDEX IDX_B190CE8FA76ED395 (user_id), INDEX IDX_B190CE8FA58ECB40 (business_unit_id), PRIMARY KEY(user_id, business_unit_id))",
            "CREATE TABLE oro_user_email (id SMALLINT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, email VARCHAR(255) NOT NULL, INDEX IDX_8600BE16A76ED395 (user_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_user_status (id SMALLINT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_D8DDF7AAA76ED395 (user_id), PRIMARY KEY(id))",

            "ALTER TABLE oro_access_group ADD CONSTRAINT FK_FEF9EDB759294170 FOREIGN KEY (business_unit_owner_id) REFERENCES oro_business_unit (id) ON DELETE SET NULL",
            "ALTER TABLE oro_access_role ADD CONSTRAINT FK_673F65E759294170 FOREIGN KEY (business_unit_owner_id) REFERENCES oro_business_unit (id) ON DELETE SET NULL",
            "ALTER TABLE oro_user ADD CONSTRAINT FK_F82840BC678BF607 FOREIGN KEY (imap_configuration_id) REFERENCES oro_email_origin (id) ON DELETE SET NULL",
            "ALTER TABLE oro_user ADD CONSTRAINT FK_F82840BC59294170 FOREIGN KEY (business_unit_owner_id) REFERENCES oro_business_unit (id) ON DELETE SET NULL",
            "ALTER TABLE oro_user ADD CONSTRAINT FK_F82840BC6BF700BD FOREIGN KEY (status_id) REFERENCES oro_user_status (id)",
            "ALTER TABLE oro_user_access_group ADD CONSTRAINT FK_EC003EF3FE54D947 FOREIGN KEY (group_id) REFERENCES oro_access_group (id) ON DELETE CASCADE",
            "ALTER TABLE oro_user_access_group ADD CONSTRAINT FK_EC003EF3A76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id) ON DELETE CASCADE",
            "ALTER TABLE oro_user_access_group_role ADD CONSTRAINT FK_E7E7E38ED60322AC FOREIGN KEY (role_id) REFERENCES oro_access_role (id) ON DELETE CASCADE",
            "ALTER TABLE oro_user_access_group_role ADD CONSTRAINT FK_E7E7E38EFE54D947 FOREIGN KEY (group_id) REFERENCES oro_access_group (id) ON DELETE CASCADE",
            "ALTER TABLE oro_user_access_role ADD CONSTRAINT FK_290571BED60322AC FOREIGN KEY (role_id) REFERENCES oro_access_role (id) ON DELETE CASCADE",
            "ALTER TABLE oro_user_access_role ADD CONSTRAINT FK_290571BEA76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id) ON DELETE CASCADE",
            "ALTER TABLE oro_user_api ADD CONSTRAINT FK_296B6993A76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id)",
            "ALTER TABLE oro_user_business_unit ADD CONSTRAINT FK_B190CE8FA58ECB40 FOREIGN KEY (business_unit_id) REFERENCES oro_business_unit (id) ON DELETE CASCADE",
            "ALTER TABLE oro_user_business_unit ADD CONSTRAINT FK_B190CE8FA76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id) ON DELETE CASCADE",
            "ALTER TABLE oro_user_email ADD CONSTRAINT FK_8600BE16A76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id)",
            "ALTER TABLE oro_user_status ADD CONSTRAINT FK_D8DDF7AAA76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id)",

            // Add user as owner to oro_email_address table
            "ALTER TABLE oro_email_address ADD COLUMN owner_user_id INT NULL AFTER id, ADD INDEX IDX_FC9DBBC52B18554A (owner_user_id);",
            "ALTER TABLE oro_email_address ADD CONSTRAINT FK_FC9DBBC52B18554A FOREIGN KEY (owner_user_id) REFERENCES oro_user (id)",
        ];
    }
}
