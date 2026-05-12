<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add order_id column to review table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE review ADD COLUMN order_id INTEGER DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_REVIEW_ORDER_ID ON review (order_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_REVIEW_ORDER_ID');
        $this->addSql('CREATE TEMPORARY TABLE __temp__review AS SELECT id, rating, comment, created_at, user_id, product_id, seller_reply, replied_at FROM review');
        $this->addSql('DROP TABLE review');
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rating INTEGER NOT NULL, comment CLOB DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, product_id INTEGER NOT NULL, seller_reply CLOB DEFAULT NULL, replied_at DATETIME DEFAULT NULL, CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_794381C64584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO review (id, rating, comment, created_at, user_id, product_id, seller_reply, replied_at) SELECT id, rating, comment, created_at, user_id, product_id, seller_reply, replied_at FROM __temp__review');
        $this->addSql('DROP TABLE __temp__review');
        $this->addSql('CREATE INDEX IDX_794381C6A76ED395 ON review (user_id)');
        $this->addSql('CREATE INDEX IDX_794381C64584665A ON review (product_id)');
    }
}