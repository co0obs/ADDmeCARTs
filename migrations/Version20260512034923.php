<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512034923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD COLUMN sale_price DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD COLUMN seller_reply CLOB DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD COLUMN replied_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__product AS SELECT id, name, description, price, stock_quantity, category, thumbnail_image, star_rating, seller_id FROM product');
        $this->addSql('DROP TABLE product');
        $this->addSql('CREATE TABLE product (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description CLOB NOT NULL, price DOUBLE PRECISION NOT NULL, stock_quantity INTEGER NOT NULL, category VARCHAR(255) NOT NULL, thumbnail_image VARCHAR(255) DEFAULT NULL, star_rating DOUBLE PRECISION DEFAULT NULL, seller_id INTEGER DEFAULT NULL, CONSTRAINT FK_D34A04AD8DE820D9 FOREIGN KEY (seller_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO product (id, name, description, price, stock_quantity, category, thumbnail_image, star_rating, seller_id) SELECT id, name, description, price, stock_quantity, category, thumbnail_image, star_rating, seller_id FROM __temp__product');
        $this->addSql('DROP TABLE __temp__product');
        $this->addSql('CREATE INDEX IDX_D34A04AD8DE820D9 ON product (seller_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__review AS SELECT id, rating, comment, created_at, user_id, product_id FROM review');
        $this->addSql('DROP TABLE review');
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rating INTEGER NOT NULL, comment CLOB DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, product_id INTEGER NOT NULL, CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_794381C64584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO review (id, rating, comment, created_at, user_id, product_id) SELECT id, rating, comment, created_at, user_id, product_id FROM __temp__review');
        $this->addSql('DROP TABLE __temp__review');
        $this->addSql('CREATE INDEX IDX_794381C6A76ED395 ON review (user_id)');
        $this->addSql('CREATE INDEX IDX_794381C64584665A ON review (product_id)');
    }
}
