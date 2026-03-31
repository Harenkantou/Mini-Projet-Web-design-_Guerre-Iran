Mini Projet Web Design - Docker (Apache + .htaccess)

Commandes rapides (PowerShell)

- Lancer et construire automatiquement les images:
	.\dev.ps1 up

- Voir les conteneurs:
	.\dev.ps1 ps

- Voir les logs en temps reel:
	.\dev.ps1 logs

- Arreter le stack:
	.\dev.ps1 down

- Rebuild complet sans cache:
	.\dev.ps1 rebuild

Commandes Docker Compose directes

- Lancer + build:
	docker compose up --build -d

- Etat des services:
	docker compose ps

- Arreter:
	docker compose down

Acces local

- Site/editeur: http://localhost:8000
- URL article SEO (rewriting): http://localhost:8000/article/{slug}
- BackOffice (connexion): http://localhost:8000/login.php
- BackOffice (editeur protege): http://localhost:8000/backoffice.php
- Adminer: http://localhost:8081
- MySQL: 127.0.0.1:3307
 
Connexion SQL depuis Docker

- Ouvrir MySQL en root:
	docker exec -it mini_projet_db mysql -u root -proot mini_projet

- Compte BackOffice de demo:
	email: admin@local.dev
	mot de passe: admin123

Important apres modification du schema SQL

- Le script database/base.sql est execute seulement au premier demarrage du volume MySQL.
- Pour reappliquer le schema et les donnees de demo:
	docker compose down -v
	docker compose up --build -d

Notes techniques

- Le projet tourne sur Apache (pas Nginx) afin de supporter le rewriting via `tinymce/.htaccess`.
- Le module `mod_rewrite` est activé dans l'image Docker.