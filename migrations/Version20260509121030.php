<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509121030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__support_ticket AS SELECT id, subject, message, status, admin_reply, created_at, user_id FROM support_ticket');
        $this->addSql('DROP TABLE support_ticket');
        $this->addSql('CREATE TABLE support_ticket (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, subject VARCHAR(255) NOT NULL, message CLOB NOT NULL, status VARCHAR(50) NOT NULL, admin_reply CLOB DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, attachment_path VARCHAR(255) DEFAULT NULL, related_order_id INTEGER DEFAULT NULL, CONSTRAINT FK_1F5A4D53A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1F5A4D532B1C2395 FOREIGN KEY (related_order_id) REFERENCES "order" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO support_ticket (id, subject, message, status, admin_reply, created_at, user_id) SELECT id, subject, message, status, admin_reply, created_at, user_id FROM __temp__support_ticket');
        $this->addSql('DROP TABLE __temp__support_ticket');
        $this->addSql('CREATE INDEX IDX_1F5A4D53A76ED395 ON support_ticket (user_id)');
        $this->addSql('CREATE INDEX IDX_1F5A4D532B1C2395 ON support_ticket (related_order_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__support_ticket AS SELECT id, subject, message, status, admin_reply, created_at, user_id FROM support_ticket');
        $this->addSql('DROP TABLE support_ticket');
        $this->addSql('CREATE TABLE support_ticket (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, subject VARCHAR(255) NOT NULL, message CLOB NOT NULL, status VARCHAR(50) NOT NULL, admin_reply CLOB DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_1F5A4D53A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO support_ticket (id, subject, message, status, admin_reply, created_at, user_id) SELECT id, subject, message, status, admin_reply, created_at, user_id FROM __temp__support_ticket');
        $this->addSql('DROP TABLE __temp__support_ticket');
        $this->addSql('CREATE INDEX IDX_1F5A4D53A76ED395 ON support_ticket (user_id)');
    }
}
