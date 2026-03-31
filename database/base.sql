CREATE DATABASE IF NOT EXISTS mini_projet;
USE mini_projet;

CREATE TABLE IF NOT EXISTS role(
   Id_role INT AUTO_INCREMENT PRIMARY KEY,
   libelle VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS categorie(
   Id_categorie INT AUTO_INCREMENT PRIMARY KEY,
   name VARCHAR(50),
   description TEXT,
   slug VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS article(
   Id_article INT AUTO_INCREMENT PRIMARY KEY,
   titre VARCHAR(500),
   contenu LONGTEXT,
   slug VARCHAR(500),
   auteur VARCHAR(500),
   created_at DATETIME,
   updated_at DATETIME,
   date_evenement DATE
);

CREATE TABLE IF NOT EXISTS categorie_article(
   Id_categorie_article INT AUTO_INCREMENT PRIMARY KEY,
   Id_categorie INT NOT NULL,
   Id_article INT NOT NULL,
   FOREIGN KEY(Id_categorie) REFERENCES categorie(Id_categorie),
   FOREIGN KEY(Id_article) REFERENCES article(Id_article)
);

CREATE TABLE IF NOT EXISTS users(
   Id_users INT AUTO_INCREMENT PRIMARY KEY,
   nom VARCHAR(50),
   prenom VARCHAR(50),
   email VARCHAR(100) UNIQUE,
   mot_de_passe VARCHAR(255),
   Id_role INT NOT NULL,
   FOREIGN KEY(Id_role) REFERENCES role(Id_role)
);



CREATE TABLE IF NOT EXISTS media(
   Id_media INT AUTO_INCREMENT PRIMARY KEY,
   path TEXT,
   alt_text VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS media_article(
   Id_media_article INT AUTO_INCREMENT PRIMARY KEY,
   Id_media INT NOT NULL,
   Id_article INT NOT NULL,
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

-- ===== CATEGORIES: Guerre Iran =====
DELETE FROM categorie_article;
DELETE FROM article;
DELETE FROM categorie;

INSERT INTO categorie (name, description, slug) VALUES
('Politique', 'Diplomatie, relations internationales et négociations', 'politique'),
('Économie', 'Sanctions, commerce et impact économique', 'economie'),
('Militaire', 'Forces armées, opérations et stratégie militaire', 'militaire'),
('Humanitaire', 'Impact humanitaire et réfugiés', 'humanitaire'),
('Analyste', 'Analyses approfondies et contexte historique', 'analyste');

-- ===== ARTICLES: Guerre Iran =====

-- Article 1: Tensions diplomatiques
INSERT INTO article (titre, contenu, slug, auteur, created_at, updated_at, date_evenement) VALUES
('Les tensions entre l\'Iran et ses voisins s\'intensifient',
'<h1>Les tensions entre l\'Iran et ses voisins s\'intensifient</h1>
<h2>Une escalade diplomatique préoccupante</h2>
<p>Depuis le début de l\'année 2026, les relations entre l\'Iran et plusieurs pays du Moyen-Orient se sont considérablement détériorées. Les échanges diplomatiques se font de plus en plus rares, tandis que les déclarations publiques se font plus agressives.</p>
<h3>Contexte géopolitique</h3>
<p>L\'Iran, puissance régionale majeure, voit son influence contestée par plusieurs acteurs. Les désaccords portent sur:</p>
<ul>
<li><strong>Le programme nucléaire</strong> : Tensions sur le respect des accords internationaux</li>
<li><strong>L\'influence régionale</strong> : Rivalité avec d\'autres puissances du Golfe</li>
<li><strong>Les proxy</strong> : Utilisation de groupes armés non-étatiques</li>
</ul>
<p>Les capitales occidentales observent la situation avec attention. Des appels à la désescalade ont été lancés par plusieurs organisations internationales.</p>',
'tensions-iran-2026',
'Correspondant Moyen-Orient',
NOW(),
NOW(),
'2026-03-28'),

-- Article 2: Enjeux économiques
('Impact des sanctions économiques sur l\'Iran',
'<h1>Impact des sanctions économiques sur l\'Iran</h1>
<h2>Une économie sous pression</h2>
<p>Les sanctions internationales imposées à l\'Iran continuent d\'affecter profondément son économie. Le pays fait face à une inflation galopante et une dévaluation de sa monnaie.</p>
<h3>Chiffres clés</h3>
<table border="1" cellpadding="10">
<tr><th>Secteur</th><th>Impact</th></tr>
<tr><td>Pétrole</td><td>Réduction de 60% des exportations</td></tr>
<tr><td>Banque</td><td>Isolement du système financier international</td></tr>
<tr><td>Commerce</td><td>Réduction drastique des échanges</td></tr>
</table>
<h3>Conséquences sociales</h3>
<p>La population iranienne subit les effets de ces mesures économiques:</p>
<ul>
<li>Augmentation du chômage</li>
<li>Perte de pouvoir d\'achat</li>
<li>Accès limité aux biens de consommation</li>
</ul>
<p>Des protestations sociales émergent dans les grandes villes, ajoutant une dimension interne à la crise.</p>',
'sanctions-iran-2026',
'Économiste en Chef',
NOW(),
NOW(),
'2026-03-25'),

-- Article 3: Enjeux militaires
('Le renforcement des capacités militaires de l\'Iran',
'<h1>Le renforcement des capacités militaires de l\'Iran</h1>
<h2>Une modernisation rapide des arsenaux</h2>
<p>Face aux tensions croissantes, l\'Iran accélère son programme de modernisation militaire. Les agences de renseignement occidentales signalent une augmentation notable des tests de missiles et du développement de nouvelles technologies.</p>
<h3>Capacités observées</h3>
<p>Les analystes militaires identifient plusieurs domaines de développement:</p>
<ul>
<li><strong>Missiles balistiques</strong> : Tests de nouveaux modèles à longue portée</li>
<li><strong>Drones</strong> : Production en masse de systèmes aériens sans pilote</li>
<li><strong>Marine</strong> : Renforcement de la présence navale en Golfe</li>
<li><strong>Cyber</strong> : Capacités cyberoffensives croissantes</li>
</ul>
<h3>Implication régionale</h3>
<p>Ces développements militaires créent un sentiment d\'insécurité chez les pays voisins, particulièrement dans les Émirats arabes unis et l\'Arabie Saoudite, entraînant une course aux armements régionale.</p>',
'militaire-iran-2026',
'Analyste Défense',
NOW(),
NOW(),
'2026-03-30'),

-- Article 4: Crise humanitaire
('La crise humanitaire s\'aggrave en Iran et dans la région',
'<h1>La crise humanitaire s\'aggrave en Iran et dans la région</h1>
<h2>Appel urgent de l\'ONU</h2>
<p>Les Nations Unies et plusieurs organisations humanitaires lancent un appel à l\'aide d\'urgence pour faire face à la crise humanitaire se développant en Iran et dans les zones de conflit adjacentes.</p>
<h3>Situation actuelle</h3>
<p>Les rapports d\'agences humanitaires révèlent une situation critique:</p>
<ul>
<li>2,5 millions de personnes en besoin d\'assistance immédiate</li>
<li>Accès limité aux services médicaux</li>
<li>Pénuries de médicaments essentiels</li>
<li>Exodes massifs de réfugiés vers les pays voisins</li>
</ul>
<h3>Réfugiés</h3>
<p>Les pays frontaliers accueillent des centaines de milliers de réfugiés. La Turquie, l\'Irak et les Émirats arabes unis font face à une pression migratoire sans précédent. Les camps de réfugiés atteignent leurs limites de capacité.</p>
<blockquote>\"La situation humanitaire exige une action rapide et coordonnée de la communauté internationale,\" déclare le coordinateur de l\'ONU.</blockquote>',
'humanitaire-iran-2026',
'Correspondant Humanitaire',
NOW(),
NOW(),
'2026-03-29'),

-- Article 5: Analyse contextuelle
('Comprendre les racines du conflit : une perspective historique',
'<h1>Comprendre les racines du conflit : une perspective historique</h1>
<h2>Au-delà des tensions actuelles</h2>
<p>Pour bien comprendre la situation actuelle en Iran et au Moyen-Orient, il faut remonter plusieurs décennies dans l\'histoire. Les tensions géopolitiques d\'aujourd\'hui ont profondément des racines historiques.</p>
<h3>Timeline historique</h3>
<p><strong>1979 : Révolution islamique</strong><br>
L\'Iran se transforme politiquement, adoptant un régime théocratique qui redéfinit sa place régionale.</p>
<p><strong>1980-1988 : Guerre Iran-Irak</strong><br>
Un conflit dévastateur qui façonne la psychologie militaire iranienne pendant des générations.</p>
<p><strong>1990s-2000s : Tensions nucléaires</strong><br>
La question du programme nucléaire iranien devient un enjeu international majeur.</p>
<p><strong>2015 : Accord JCPOA</strong><br>
Un moment d\'espoir diplomatique et de levée partielle des sanctions.</p>
<p><strong>2018 : Retrait américain de l\'accord</strong><br>
L\'administration américaine se retire du JCPOA, relançant les tensions.</p>
<h3>Enjeux actuels</h3>
<p>L\'Iran voit les sanctions internationales comme illégitimes et une tentative d\'isolement. La rivalité avec les monarchies du Golfe, soutenues par les puissances occidentales, alimente une vision régionale de conflit direct entre systèmes politiques opposés.</p>',
'histoire-iran-conflit',
'Historien Régional',
NOW(),
NOW(),
'2026-03-27');

-- ===== MEDIAS: Images pour les articles =====

INSERT INTO media (path, alt_text) VALUES
('/images/article1.jpg', 'Tensions diplomatiques entre l\'Iran et ses voisins régionaux'),
('/images/article2.jpg', 'Impact économique des sanctions sur l\'Iran'),
('/images/article3.jpg', 'Capacités militaires et arsenaux de l\'Iran'),
('/images/article4.jpg', 'Crise humanitaire et réfugiés en fuite d\'Iran'),
('/images/article5.jpg', 'Contexte historique du Moyen-Orient et de l\'Iran');

-- ===== ASSOCIATIONS CATEGORIES <-> ARTICLES =====

-- Article 1: Politique - Tensions
INSERT INTO categorie_article (Id_categorie, Id_article)
SELECT c.Id_categorie, a.Id_article
FROM categorie c, article a
WHERE c.slug = 'politique' AND a.slug = 'tensions-iran-2026';

-- Article 2: Économie - Sanctions
INSERT INTO categorie_article (Id_categorie, Id_article)
SELECT c.Id_categorie, a.Id_article
FROM categorie c, article a
WHERE c.slug = 'economie' AND a.slug = 'sanctions-iran-2026';

-- Article 3: Militaire
INSERT INTO categorie_article (Id_categorie, Id_article)
SELECT c.Id_categorie, a.Id_article
FROM categorie c, article a
WHERE c.slug = 'militaire' AND a.slug = 'militaire-iran-2026';

-- Article 4: Humanitaire
INSERT INTO categorie_article (Id_categorie, Id_article)
SELECT c.Id_categorie, a.Id_article
FROM categorie c, article a
WHERE c.slug = 'humanitaire' AND a.slug = 'humanitaire-iran-2026';

-- Article 5: Analyste - Histoire
INSERT INTO categorie_article (Id_categorie, Id_article)
SELECT c.Id_categorie, a.Id_article
FROM categorie c, article a
WHERE c.slug = 'analyste' AND a.slug = 'histoire-iran-conflit';

-- ===== ASSOCIATIONS MEDIA <-> ARTICLES =====

-- Image 1 -> Article 1 (Tensions)
INSERT INTO media_article (Id_media, Id_article)
SELECT m.Id_media, a.Id_article
FROM media m, article a
WHERE m.path = '/images/article1.jpg' AND a.slug = 'tensions-iran-2026';

-- Image 2 -> Article 2 (Sanctions)
INSERT INTO media_article (Id_media, Id_article)
SELECT m.Id_media, a.Id_article
FROM media m, article a
WHERE m.path = '/images/article2.jpg' AND a.slug = 'sanctions-iran-2026';

-- Image 3 -> Article 3 (Militaire)
INSERT INTO media_article (Id_media, Id_article)
SELECT m.Id_media, a.Id_article
FROM media m, article a
WHERE m.path = '/images/article3.jpg' AND a.slug = 'militaire-iran-2026';

-- Image 4 -> Article 4 (Humanitaire)
INSERT INTO media_article (Id_media, Id_article)
SELECT m.Id_media, a.Id_article
FROM media m, article a
WHERE m.path = '/images/article4.jpg' AND a.slug = 'humanitaire-iran-2026';

-- Image 5 -> Article 5 (Histoire)
INSERT INTO media_article (Id_media, Id_article)
SELECT m.Id_media, a.Id_article
FROM media m, article a
WHERE m.path = '/images/article5.jpg' AND a.slug = 'histoire-iran-conflit';
