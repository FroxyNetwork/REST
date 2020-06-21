# REST
API Rest pour le Projet Froxy-Network

Ce dépôt contient les sources et la documentation pour la partie REST.

## Documents
Comment [aider](https://github.com/FroxyNetwork/REST/blob/develop/docs/howto_help.md) pour le projet

## Librairies
  - [PHPUnit](https://phpunit.de/)
  - [OAuth2-server-php](https://bshaffer.github.io/oauth2-server-php-docs/)

## License
This software is available under the following licenses:

  - MIT

## Execution
Pour executer cette partie:
  - Téléchargez le repository
  - Copiez tout le contenu sur votre serveur web
  - Executez la commande "composer install"
  - Créez une base de données "froxynetwork"
  - Executez la requête mongodb se trouvant dans le fichier api/controller/datasourcecontroller/froxynetwork.mongodb
  - Rendez-vous dans le dossier "extra"
  - Entrez cette commande: "php generate_client_id_secret.php" et copiez la requête
  - Copiez la requête affichée
  - Allez sur votre base de données
  - Executez la requête
