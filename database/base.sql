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
   alt_text VARCHAR(255),
   PRIMARY KEY(Id_media)
);

ALTER TABLE media
ADD COLUMN IF NOT EXISTS alt_text VARCHAR(255) NULL AFTER path;

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

-- Donnees d'exemple pour les categories
INSERT INTO categorie (name, description, slug)
SELECT 'Politique', 'Articles sur la politique et diplomatie', 'politique'
WHERE NOT EXISTS (SELECT 1 FROM categorie WHERE slug = 'politique');

INSERT INTO categorie (name, description, slug)
SELECT 'economie', 'Analyse economique et sanctions', 'economie'
WHERE NOT EXISTS (SELECT 1 FROM categorie WHERE slug = 'economie');

INSERT INTO categorie (name, description, slug)
SELECT 'Militaire', 'Informations militaires et defense', 'militaire'
WHERE NOT EXISTS (SELECT 1 FROM categorie WHERE slug = 'militaire');

INSERT INTO categorie (name, description, slug)
SELECT 'International', 'Relations internationales', 'international'
WHERE NOT EXISTS (SELECT 1 FROM categorie WHERE slug = 'international');

INSERT INTO categorie (name, description, slug)
SELECT 'Culture', 'Actualites culturelles et sociales', 'culture'
WHERE NOT EXISTS (SELECT 1 FROM categorie WHERE slug = 'culture');

-- Donnees d'exemple pour les articles
INSERT INTO article (titre, contenu, slug, auteur, created_at, updated_at, date_evenement)
SELECT 
    'Premier article d\'exemple',
    'Ceci est le contenu d\'un article d\'exemple.',
    'premier-article',
    'Admin',
    NOW(),
    NOW(),
    CURDATE()
WHERE NOT EXISTS (SELECT 1 FROM article WHERE slug = 'premier-article');

INSERT INTO article (titre, contenu, slug, auteur, created_at, updated_at, date_evenement)
SELECT 
    'Deuxième article d\'exemple',
    'Contenu du deuxième article avec plus de details...',
    'deuxieme-article',
    'Admin',
    NOW(),
    NOW(),
    CURDATE()
WHERE NOT EXISTS (SELECT 1 FROM article WHERE slug = 'deuxieme-article');

-- Associations catégories<->articles (exemple)
INSERT INTO categorie_article (Id_categorie, Id_article)
SELECT c.Id_categorie, a.Id_article
FROM categorie c
CROSS JOIN article a
WHERE c.slug = 'politique' AND a.slug = 'premier-article'
  AND NOT EXISTS (
     SELECT 1 FROM categorie_article ca
     WHERE ca.Id_categorie = c.Id_categorie
       AND ca.Id_article = a.Id_article
);

INSERT INTO categorie_article (Id_categorie, Id_article)
SELECT c.Id_categorie, a.Id_article
FROM categorie c
CROSS JOIN article a
WHERE c.slug = 'economie' AND a.slug = 'deuxieme-article'
  AND NOT EXISTS (
     SELECT 1 FROM categorie_article ca
     WHERE ca.Id_categorie = c.Id_categorie
       AND ca.Id_article = a.Id_article
);

INSERT INTO categorie_article (Id_categorie, Id_article)
SELECT c.Id_categorie, a.Id_article
FROM categorie c
CROSS JOIN article a
WHERE c.slug = 'politique' AND a.slug = 'deuxieme-article'
  AND NOT EXISTS (
     SELECT 1 FROM categorie_article ca
     WHERE ca.Id_categorie = c.Id_categorie
       AND ca.Id_article = a.Id_article
);

-- Article Culture
INSERT INTO article (titre, contenu, slug, auteur, created_at, updated_at, date_evenement)
SELECT 
    '<h1>Festival culturel : retour sur la semaine d\'échanges</h1>',
    '<h2>Un rendez-vous annuel incontournable</h2><p>Cette année, le <strong>Festival international des cultures</strong> a réuni plus de 150 artistes.</p><ul><li>Musique</li><li>Danse</li><li>Peinture</li></ul><p>Nous avons couvert en direct chaque soirée sur le terrain, avec interviews et galeries photos.</p><blockquote>«La culture unit, même en période de tension», déclare le commissaire.</blockquote>',
    'festival-culture-2026',
    'Rédaction Culture',
    NOW(),
    NOW(),
    '2026-03-27'
WHERE NOT EXISTS (SELECT 1 FROM article WHERE slug = 'festival-culture-2026');

-- Article Militaire
INSERT INTO article (titre, contenu, slug, auteur, created_at, updated_at, date_evenement)
SELECT 
    '<h1>Manœuvres stratégiques : étape finale dans le secteur nord</h1>',
    '<h2>Manœuvres conjointes</h2><p>Les forces impliquées ont mené un exercice de défense anti-drone.</p><ol><li>Préparation logistique</li><li>Simulation de réaction</li><li>Évaluation des pertes simulées</li></ol><p>Le général a indiqué que <strong>la collaboration internationale</strong> est essentielle pour maintenir la stabilité.</p><pre>Zone de déploiement : secteur nord / Code opération : VIGIE-12</pre>',
    'manoeuvres-nord-2026',
    'Capitaine Tremblay',
    NOW(),
    NOW(),
    '2026-03-30'
WHERE NOT EXISTS (SELECT 1 FROM article WHERE slug = 'manoeuvres-nord-2026');

-- Liens Culture <-> Festival article
INSERT INTO categorie_article (Id_categorie, Id_article)
SELECT c.Id_categorie, a.Id_article
FROM categorie c
CROSS JOIN article a
WHERE c.slug = 'culture' AND a.slug = 'festival-culture-2026'
  AND NOT EXISTS (
     SELECT 1 FROM categorie_article ca
     WHERE ca.Id_categorie = c.Id_categorie
       AND ca.Id_article = a.Id_article
);

-- Liens Militaire <-> Manoeuvres article
INSERT INTO categorie_article (Id_categorie, Id_article)
SELECT c.Id_categorie, a.Id_article
FROM categorie c
CROSS JOIN article a
WHERE c.slug = 'militaire' AND a.slug = 'manoeuvres-nord-2026'
  AND NOT EXISTS (
     SELECT 1 FROM categorie_article ca
     WHERE ca.Id_categorie = c.Id_categorie
       AND ca.Id_article = a.Id_article
);
