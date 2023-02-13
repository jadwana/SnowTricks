<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230213075922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE videos ADD tricks_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA64323B153154 FOREIGN KEY (tricks_id) REFERENCES tricks (id)');
        $this->addSql('CREATE INDEX IDX_29AA64323B153154 ON videos (tricks_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA64323B153154');
        $this->addSql('DROP INDEX IDX_29AA64323B153154 ON videos');
        $this->addSql('ALTER TABLE videos DROP tricks_id');
    }
}
