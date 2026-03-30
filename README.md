Mini Projet Web Design - Docker

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
- Adminer: http://localhost:8081
- MySQL: 127.0.0.1:3307
 
docker exec -it mini_projet_db mysql -u root -proot mini_projet 