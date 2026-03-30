CREATE DATABASE IF NOT EXISTS mini_projet;

USE mini_projet;


CREATE TABLE role(
   Id_role INT AUTO_INCREMENT,
   libelle VARCHAR(50) ,
   PRIMARY KEY(Id_role)
);

CREATE TABLE categorie(
   Id_categorie INT AUTO_INCREMENT,
   name VARCHAR(50) ,
   description TEXT,
   slug VARCHAR(50) ,
   PRIMARY KEY(Id_categorie)
);

CREATE TABLE article(
   Id_article INT AUTO_INCREMENT,
   titre VARCHAR(500) ,
   contenu TEXT,
   slug VARCHAR(500) ,
   auteur VARCHAR(500) ,
   created_at DATETIME,
   updated_at DATETIME,
   date_evenement DATE,
   PRIMARY KEY(Id_article)
);

CREATE TABLE categorie_article(
   Id_categorie_article INT AUTO_INCREMENT,
   Id_categorie INT NOT NULL,
   Id_article INT NOT NULL,
   PRIMARY KEY(Id_categorie_article),
   FOREIGN KEY(Id_categorie) REFERENCES categorie(Id_categorie),
   FOREIGN KEY(Id_article) REFERENCES article(Id_article)
);

CREATE TABLE type_media(
   Id_type_media INT AUTO_INCREMENT,
   libelle VARCHAR(50) ,
   PRIMARY KEY(Id_type_media)
);

CREATE TABLE users(
   Id_users INT AUTO_INCREMENT,
   nom VARCHAR(50) ,
   prenom VARCHAR(50) ,
   email VARCHAR(100) ,
   mot_de_passe VARCHAR(255) ,
   Id_role INT NOT NULL,
   PRIMARY KEY(Id_users),
   UNIQUE KEY uk_users_email (email),
   FOREIGN KEY(Id_role) REFERENCES role(Id_role)
);

CREATE TABLE documents(
   id INT AUTO_INCREMENT,
   title VARCHAR(255) NOT NULL,
   content LONGTEXT,
   created_at DATETIME NOT NULL,
   updated_at DATETIME NOT NULL,
   PRIMARY KEY(id)
);

CREATE TABLE media(
   Id_media INT AUTO_INCREMENT,
   path TEXT,
   Id_type_media INT NOT NULL,
   PRIMARY KEY(Id_media),
   FOREIGN KEY(Id_type_media) REFERENCES type_media(Id_type_media)
);

CREATE TABLE media_article(
   Id_media_article INT AUTO_INCREMENT,
   Id_media INT NOT NULL,
   Id_article INT NOT NULL,
   PRIMARY KEY(Id_media_article),
   FOREIGN KEY(Id_media) REFERENCES media(Id_media),
   FOREIGN KEY(Id_article) REFERENCES article(Id_article)
);

INSERT INTO role (libelle)
SELECT 'admin'
WHERE NOT EXISTS (SELECT 1 FROM role WHERE libelle = 'admin');

INSERT INTO users (nom, prenom, email, mot_de_passe, Id_role)
SELECT 'Admin', 'Systeme', 'admin@local.dev', 'admin123', Id_role
FROM role
WHERE libelle = 'admin'
   AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@local.dev');
