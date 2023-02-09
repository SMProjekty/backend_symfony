<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221220170856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, surname VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE offer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, price INT NOT NULL, time INT NOT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vacation (id INT AUTO_INCREMENT NOT NULL, worker_id INT NOT NULL, datefrom DATE NOT NULL, dateto DATE NOT NULL, INDEX IDX_E3DADF756B20BA36 (worker_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE visit (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, worker_id INT NOT NULL, offer_id INT NOT NULL, date DATE NOT NULL, time TIME NOT NULL, status TINYINT(1) NOT NULL, INDEX IDX_437EE9399395C3F3 (customer_id), INDEX IDX_437EE9396B20BA36 (worker_id), INDEX IDX_437EE93953C674EE (offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE worker (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(30) NOT NULL, surname VARCHAR(30) NOT NULL, active TINYINT(1) NOT NULL, color VARCHAR(10) NOT NULL, photo VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE vacation ADD CONSTRAINT FK_E3DADF756B20BA36 FOREIGN KEY (worker_id) REFERENCES worker (id)');
        $this->addSql('ALTER TABLE visit ADD CONSTRAINT FK_437EE9399395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE visit ADD CONSTRAINT FK_437EE9396B20BA36 FOREIGN KEY (worker_id) REFERENCES worker (id)');
        $this->addSql('ALTER TABLE visit ADD CONSTRAINT FK_437EE93953C674EE FOREIGN KEY (offer_id) REFERENCES offer (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vacation DROP FOREIGN KEY FK_E3DADF756B20BA36');
        $this->addSql('ALTER TABLE visit DROP FOREIGN KEY FK_437EE9399395C3F3');
        $this->addSql('ALTER TABLE visit DROP FOREIGN KEY FK_437EE9396B20BA36');
        $this->addSql('ALTER TABLE visit DROP FOREIGN KEY FK_437EE93953C674EE');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE offer');
        $this->addSql('DROP TABLE vacation');
        $this->addSql('DROP TABLE visit');
        $this->addSql('DROP TABLE worker');
    }
}
