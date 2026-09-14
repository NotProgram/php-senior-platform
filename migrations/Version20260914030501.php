<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260914030501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE review_cards (id INT AUTO_INCREMENT NOT NULL, card_id VARCHAR(190) NOT NULL, lesson_slug VARCHAR(150) NOT NULL, kind VARCHAR(30) NOT NULL, ease_factor DOUBLE PRECISION NOT NULL, interval_days INT NOT NULL, repetitions INT NOT NULL, lapses INT NOT NULL, due_at DATETIME NOT NULL, last_reviewed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX idx_review_due (user_id, due_at), UNIQUE INDEX uniq_user_card (user_id, card_id), INDEX IDX_174C74E6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE review_cards ADD CONSTRAINT FK_174C74E6A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review_cards DROP FOREIGN KEY FK_174C74E6A76ED395');
        $this->addSql('DROP TABLE review_cards');
    }
}
