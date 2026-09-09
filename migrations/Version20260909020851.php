<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260909020851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exercise_attempts (id INT AUTO_INCREMENT NOT NULL, lesson_slug VARCHAR(150) NOT NULL, submitted_code LONGTEXT NOT NULL, is_passed TINYINT NOT NULL, feedback LONGTEXT DEFAULT NULL, executed_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX idx_attempt_user_lesson (user_id, lesson_slug), INDEX IDX_B9AB478CA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quiz_attempts (id INT AUTO_INCREMENT NOT NULL, lesson_slug VARCHAR(150) NOT NULL, answers JSON NOT NULL, score_percentage INT NOT NULL, passed TINYINT NOT NULL, attempted_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX idx_quiz_user_lesson (user_id, lesson_slug), INDEX IDX_69031E21A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE system_design_states (id INT AUTO_INCREMENT NOT NULL, scenario_slug VARCHAR(150) NOT NULL, nodes_config JSON NOT NULL, connections_config JSON NOT NULL, notes LONGTEXT DEFAULT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX uniq_user_scenario (user_id, scenario_slug), INDEX IDX_EDA1A8DBA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_progress (id INT AUTO_INCREMENT NOT NULL, module_slug VARCHAR(100) NOT NULL, lesson_slug VARCHAR(150) NOT NULL, status VARCHAR(30) NOT NULL, score INT NOT NULL, notes LONGTEXT DEFAULT NULL, completed_at DATETIME DEFAULT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX idx_user_module (user_id, module_slug), UNIQUE INDEX uniq_user_lesson (user_id, lesson_slug), INDEX IDX_C28C1646A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, display_name VARCHAR(100) NOT NULL, current_level VARCHAR(50) NOT NULL, experience_points INT NOT NULL, streak_days INT NOT NULL, created_at DATETIME NOT NULL, last_active_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE exercise_attempts ADD CONSTRAINT FK_B9AB478CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quiz_attempts ADD CONSTRAINT FK_69031E21A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE system_design_states ADD CONSTRAINT FK_EDA1A8DBA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_progress ADD CONSTRAINT FK_C28C1646A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exercise_attempts DROP FOREIGN KEY FK_B9AB478CA76ED395');
        $this->addSql('ALTER TABLE quiz_attempts DROP FOREIGN KEY FK_69031E21A76ED395');
        $this->addSql('ALTER TABLE system_design_states DROP FOREIGN KEY FK_EDA1A8DBA76ED395');
        $this->addSql('ALTER TABLE user_progress DROP FOREIGN KEY FK_C28C1646A76ED395');
        $this->addSql('DROP TABLE exercise_attempts');
        $this->addSql('DROP TABLE quiz_attempts');
        $this->addSql('DROP TABLE system_design_states');
        $this->addSql('DROP TABLE user_progress');
        $this->addSql('DROP TABLE users');
    }
}
