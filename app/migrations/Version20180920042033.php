<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20180920042033 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
          CREATE TABLE turnkey_projects_files
          (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            path VARCHAR(255) NOT NULL,
            extension VARCHAR(255) NOT NULL,
            UNIQUE INDEX turnkey_projects_file_slug_extension (slug, extension),
            PRIMARY KEY(id)
          ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB
SQL
        );
        $this->addSql(<<<'SQL'
          CREATE TABLE turnkey_projects_has_files
          (
            turnkey_project_id INT UNSIGNED NOT NULL,
            turnkey_project_file_id INT UNSIGNED NOT NULL,
            INDEX IDX_B851654CB5315DF4 (turnkey_project_id),
            INDEX IDX_B851654C7D06E1CD (turnkey_project_file_id),
            PRIMARY KEY(turnkey_project_id, turnkey_project_file_id)
          ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB
SQL
        );
        $this->addSql('ALTER TABLE turnkey_projects_has_files ADD CONSTRAINT FK_B851654CB5315DF4 FOREIGN KEY (turnkey_project_id) REFERENCES turnkey_projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE turnkey_projects_has_files ADD CONSTRAINT FK_B851654C7D06E1CD FOREIGN KEY (turnkey_project_file_id) REFERENCES turnkey_projects_files (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE turnkey_projects_has_files DROP FOREIGN KEY FK_B851654C7D06E1CD');
        $this->addSql('DROP TABLE turnkey_projects_files');
        $this->addSql('DROP TABLE turnkey_projects_has_files');
    }
}
