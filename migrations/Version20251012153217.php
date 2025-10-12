<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251012153217 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE albums (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, cover_image VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, photo_count INT NOT NULL, view_count INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_F4E2474FD17F50A6 (uuid), INDEX IDX_F4E2474FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE album_photos (album_id INT NOT NULL, photo_id INT NOT NULL, INDEX IDX_DA0DDD6E1137ABCF (album_id), INDEX IDX_DA0DDD6E7E9E4C8C (photo_id), PRIMARY KEY(album_id, photo_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE album_tags (album_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_BB722661137ABCF (album_id), INDEX IDX_BB72266BAD26311 (tag_id), PRIMARY KEY(album_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE photos (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, filename VARCHAR(255) NOT NULL, filename_thumbnail VARCHAR(255) DEFAULT NULL, filename_tablet VARCHAR(255) DEFAULT NULL, filename_desktop VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, view_count INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', uploaded_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', copyright VARCHAR(255) DEFAULT NULL, device VARCHAR(255) DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, aperture NUMERIC(3, 1) DEFAULT NULL, focal_length NUMERIC(5, 1) DEFAULT NULL, exposure_time VARCHAR(50) DEFAULT NULL, iso INT DEFAULT NULL, flash TINYINT(1) NOT NULL, view_privacy VARCHAR(20) NOT NULL, like_count INT NOT NULL, rating SMALLINT DEFAULT NULL, UNIQUE INDEX UNIQ_876E0D9D17F50A6 (uuid), INDEX IDX_876E0D9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE photo_tags (photo_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_EE8D26D27E9E4C8C (photo_id), INDEX IDX_EE8D26D2BAD26311 (tag_id), PRIMARY KEY(photo_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tags (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_6FBC94265E237E06 (name), UNIQUE INDEX UNIQ_6FBC9426989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(255) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE albums ADD CONSTRAINT FK_F4E2474FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE album_photos ADD CONSTRAINT FK_DA0DDD6E1137ABCF FOREIGN KEY (album_id) REFERENCES albums (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE album_photos ADD CONSTRAINT FK_DA0DDD6E7E9E4C8C FOREIGN KEY (photo_id) REFERENCES photos (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE album_tags ADD CONSTRAINT FK_BB722661137ABCF FOREIGN KEY (album_id) REFERENCES albums (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE album_tags ADD CONSTRAINT FK_BB72266BAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photos ADD CONSTRAINT FK_876E0D9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE photo_tags ADD CONSTRAINT FK_EE8D26D27E9E4C8C FOREIGN KEY (photo_id) REFERENCES photos (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photo_tags ADD CONSTRAINT FK_EE8D26D2BAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE albums DROP FOREIGN KEY FK_F4E2474FA76ED395');
        $this->addSql('ALTER TABLE album_photos DROP FOREIGN KEY FK_DA0DDD6E1137ABCF');
        $this->addSql('ALTER TABLE album_photos DROP FOREIGN KEY FK_DA0DDD6E7E9E4C8C');
        $this->addSql('ALTER TABLE album_tags DROP FOREIGN KEY FK_BB722661137ABCF');
        $this->addSql('ALTER TABLE album_tags DROP FOREIGN KEY FK_BB72266BAD26311');
        $this->addSql('ALTER TABLE photos DROP FOREIGN KEY FK_876E0D9A76ED395');
        $this->addSql('ALTER TABLE photo_tags DROP FOREIGN KEY FK_EE8D26D27E9E4C8C');
        $this->addSql('ALTER TABLE photo_tags DROP FOREIGN KEY FK_EE8D26D2BAD26311');
        $this->addSql('DROP TABLE albums');
        $this->addSql('DROP TABLE album_photos');
        $this->addSql('DROP TABLE album_tags');
        $this->addSql('DROP TABLE photos');
        $this->addSql('DROP TABLE photo_tags');
        $this->addSql('DROP TABLE tags');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
