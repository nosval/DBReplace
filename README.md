DBReplace
=========


DBReplace va vous permettre de remplacer une ou plusieurs chaines de caractères sur toute une base de données MySQL en utf-8, y compris dans les champs serialize de **Wordpress** !

Le petit script parfait et léger pour migrer votre simple **Wordpress** d'une url à une autre.

Il inclut la class SimplePDO pour faciliter les appels à PDO.


## Configurer PDO

Dans le fichier DBReplace.php, éditez les informations de connexion à votre base de données :

    $database = array(
        "type" => "mysql",
        "host" => "localhost",
        "database" => "DATABASE",
        "user" => "USERNAME",
        "pass" => "PASSWORD"
    );

## Chaînes de caractères

Entrez dans le tableau associatif $changes la liste des champs que vous souhaitez remplacer dans votre base.

    $changes = array(
        'search' => 'destination',
    );

## Lancer le script

Il est important de sauvegarder votre base de données avant de lancer le script. Je n'ai testé le script que sur une configuration classique de MySQL.

Pour le lancer, appeler tout simplement l'url du fichier via votre natigateur. Et c'est fait !
Si rien ne s'affiche, c'est normal. Vérifiez toutefois que les changements ont été appliqués.


## Nouveautés à venir

Si DBReplace ou SimplePDO vous intéressent, je prendrai le temps de les améliorer en suivant vos besoins.

 - Mode debug pour afficher les erreurs et réussites
 - Remplacer les chaînes dans .htaccess et wp-config.php
 - Appel en une ligne de commande
 - Copier entièrement une base de données en remplaçant les chaînes.
 - Autoriser ou exclure certaines tables
 - Création d'un Plugin Wordpress avec une jolie interface

 N'hésitez pas à me rapporter vos bugs et proposer vos corrections et améliorations.