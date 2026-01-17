#  Application Web de Gestion des Logs – Symfony

##  Description du projet

Ce projet est un **projet académique réalisé dans le cadre de la formation à l’École Marocaine des Sciences d’Ingénieur (EMSI)**.  
Il consiste à développer une **application web de gestion des logs** en utilisant le **framework Symfony**.

L’objectif principal est de mettre en pratique les concepts fondamentaux du développement web avec Symfony, notamment l’architecture MVC, la gestion de base de données, les formulaires, la sécurité et l’utilisation d’UML.

---

##  Contexte académique

- **École :** École Marocaine des Sciences d’Ingénieur (EMSI)  
- **Filière :** Ingénierie Informatique et Réseaux  
- **Type :** Projet académique / TP encadré  



##  Objectifs du projet

- Découvrir le framework **Symfony**
- Comprendre l’architecture **MVC**
- Connecter une application web à une base de données
- Implémenter les opérations **CRUD**
- Utiliser **Doctrine ORM**
- Créer des vues avec **Twig**
- Mettre en place la **sécurité CSRF**
- Concevoir l’application avec **UML**

---

##  Technologies utilisées

- **PHP 8**
- **Symfony Framework**
- **Doctrine ORM**
- **Twig**
- **MySQL / MariaDB**
- **Bootstrap**
- **PlantUML**

---

##  Architecture du projet

L’application suit l’architecture **MVC (Model – View – Controller)** :

- **Model :** Entité `LogEvent`
- **View :** Templates Twig
- **Controller :** `LogEventController`
- **Repository :** `LogEventRepository`

Les logs sont stockés dans une base de données et gérés via un dashboard web.

---

##  Fonctionnalités principales

###  Dashboard
- Affichage paginé des logs
- Tri par date décroissante

###  Filtres
- Par adresse IP
- Par type d’événement
- Par niveau (level)
- Recherche par mot-clé

###  Gestion des logs
- Suppression d’un log
- Suppression de tous les logs
- Suppression des logs anciens (5 heures / 10 jours)

###  Sécurité
- Protection CSRF pour toutes les actions sensibles
- Validation des formulaires Symfony

---

##  Conception UML

Le projet est conçu à l’aide de diagrammes UML :
- Diagramme de classes
- Diagramme de séquence
- Diagramme de cas d’utilisation

Ces diagrammes permettent de mieux comprendre le fonctionnement global de l’application.

---

##  Installation du projet (local)

```bash
git clone https://github.com/Youneselbarj/Symfony_Project.git
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony serve
