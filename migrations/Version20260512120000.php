<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add delivery address fields to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD COLUMN address1 VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN address2 VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN address3 VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN default_address_index INTEGER DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, first_name, last_name, security_pin, store_name FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, security_pin VARCHAR(4) NOT NULL, store_name VARCHAR(255) DEFAULT NULL, cart_id INTEGER DEFAULT NULL, CONSTRAINT FK_8D93D6498DE820D9 FOREIGN KEY (cart_id) REFERENCES cart (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user (id, email, roles, password, first_name, last_name, security_pin, store_name) SELECT id, email, roles, password, first_name, last_name, security_pin, store_name FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('CREATE INDEX IDX_8D93D6498DE820D9 ON user (cart_id)');
    }
}