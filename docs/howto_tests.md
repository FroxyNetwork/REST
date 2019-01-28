# Comment tester ?

## Pré-réquis
Vous devez avoir **[xdebug](https://xdebug.org/)** installé pour pouvoir lancer le "code coverage"

## Configuration
### Intellij Idea
Le fichier de configuration de phpunit est situé à la racine (phpunit.xml).
Vous devez configurer Intellij Idea pour pouvoir lancer les tests automatiquement.
1. File => Settings => Languages & Frameworks => PHP => Test Frameworks
2. Appuyez sur le "plus" (+)
3. Entrez un nom (PHPUnit)
4. "Use Composer Auto-Loader" => Renseignez le chemin vers le fichier Api/Vendor/autoload.php
5. "Default configuration file:" => Renseignez le chemin vers le fichier phpunit.xml
6. OK
7. Pour lancer un test, sélectionnez la configuration créée et appuyez sur "RUN"
8. Pour lancer un test coverage, sélectionnez la configuration créée et appuyez sur "RUN with Coverage"

## Informations utiles
N'oubliez pas de lire [howto_help](https://github.com/FroxyNetwork/REST/blob/develop/docs/howto_help.md)
