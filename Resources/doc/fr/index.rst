Documentation du DaLessBundle
=============================

Le DaLessBundle permet de compiler des fichiers less en css en n'ayant rien d'autre à installer que ce bundle.

Accéder à l'interface de compilation
------------------------------------

Une interface permet de lancer les différents types de compilations disponibles:

* La compilation via un formulaire
* La compilation via un fichier de configuration 

Elle est accessible à l'url:

.. code-block:: bash

    /__da/less

Compilation via un formulaire
-----------------------------

Il est possible d'utiliser un formulaire pour configurer et exécuter une compilation.

Définition des paramètres
~~~~~~~~~~~~~~~~~~~~~~~~~

Les 4 paramètres configurables sont:

* | **Le répertoire par défaut:**
  | C'est le chemin du répertoire où se trouve le ou les fichiers less (si il y a des imports) qui vont servir lors de la compilation.

* | **Le répertoire de surcharge:**
  | Dans le cas où il y a plusieurs fichiers less dans le répertoire par défaut, on peut définir le chemin d'un répertoire de 
    surcharge dont les fichiers viendront écraser ceux du répertoire par défaut lors de la compilation (bien sûr pas les originaux).
    Ceci est notamment pratique lorsque l'on a un des fichiers less qui sert au paramétrage des valeurs des variables less et que
    l'on souhaite en avoir plusieurs déclinaisons, par exemple.

* | **Le fichier source:**
  | C'est le nom du fichier less source de la compilation. Si le répertoire par défaut n'est pas renseigné, il faut préciser le chemin
    du fichier également.

* | **Le fichier de destination:**
  | C'est le nom et le chemin du fichier css de destination.

Format des paramètres
~~~~~~~~~~~~~~~~~~~~~

Pour des raisons de sécurité évidentes, il n'est pas possible d'accéder à n'importe quel fichier du disque.
Les fichiers sources doivent forcément se trouver dans un répertoire du type:

.. code-block:: bash

    {bundle_root_dir}/Resources/private/less # pour les repertoires et fichiers less.
    {bundle_root_dir}/Resources/public/css # pour les fichiers css.

Bien entendu, les fichiers peuvent se trouver dans n'importe quel sous-répertoire de celui-ci.

Un sucre syntaxique a été implémenté pour éviter que la répétition de ce chemin soit trop fastidueuse:

.. code-block:: bash

    BundleName:path/to/directory/or/file

* **BundleName** représente le nom du bundle dans Symfony (DaLessBundle pour ce bundle par exemple).
* **path/to/directory/or/file** est le chemin vers le répertoire ou le fichier à partir des répertoires definis ci-dessus.

Ce qui donne, pour les répertoires par défaut et de surcharge:

.. code-block:: bash

    MySuperBundle:themes/aqua
    # équivaut au répertoire /src/My/SuperBundle/Resources/private/less/themes/aqua
    MySuperBundle:
    # équivaut au répertoire /src/My/SuperBundle/Resources/private/less

De même, on a, pour les fichiers source et destination:

.. code-block:: bash

    MySuperBundle:themes/aqua/mystyle
    # équivaut au fichier /src/My/SuperBundle/Resources/private/less/themes/aqua/mystyle.less pour une source.
    # équivaut au fichier /src/My/SuperBundle/Resources/public/css/themes/aqua/mystyle.css pour une destination.

Compilation via un fichier de configuration
-------------------------------------------

Il est possible d'utiliser un fichier de configuration pour paramétrer des compilations que l'on souhaite réexécuter.

Format de la configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. configuration-block::

    .. code-block:: yaml

        # /app/config/config.yml
        da_less:
            compilation:
                bootstrap:
                    default: "BootstrapBundle:"
                    override: "MySuperBundle:bootstrap"
                    source: bootstrap
                    destination: "MySuperBundle:bootstrap"
                custom:
                    default: "MySuperBundle:"
                    override:
                    source: custom
                    destination: "MySuperBundle:custom"

Dans cet exemple, bootstrap et custom sont des identifiants de compilation. Comme pour la compilation via un formulaire, 
il est possible d'utiliser la notation simplifiée.

Dans le cas de la première compilation d'id bootstrap, on aura les fichiers du repertoire /src/Resources/private/less du
BootstrapBundle qui seront écrasés par les fichiers du répertoire /src/Resources/private/less/bootstrap du MySuperBundle. 
Bootstrap est une bibliothèque css compilée à partir de fichiers less que l'on peut personnaliser en changeant les valeurs 
contenues dans le fichier variables.less. On peut donc imaginer que l'on a un fichier variables.less dans le répertoire 
/src/Resources/private/less/bootstrap du MySuperBundle. Le fichier source de la compilation est bootstrap.less (issu de la
fusion des répertoires par défaut et de surcharge) et le fichier de destination est /src/Resources/public/css/bootstrap.css.

.. tip::

    Il est possible de simplifier l'écriture quand il n'y a pas de répertoire de surcharge. Ainsi,

    .. code-block:: yaml

        # /app/config/config.yml
        da_less:
            compilation:
                custom:
                    default: "MySuperBundle:"
                    override:
                    source: custom
                    destination: "MySuperBundle:custom"

    est équivalent à

    .. code-block:: yaml

        # /app/config/config.yml
        da_less:
            compilation:
                custom:
                    source: "MySuperBundle:custom"
                    destination: "MySuperBundle:custom"

Exécution d'une compilation configurée
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Pour exécuter une compilation configurée, il suffit de passer par l'interface ou d'utiliser l'url:

.. code-block:: bash

    /__da/less/compile/{compilation_id}

Pour exécuter toutes les compilations configurées, il suffit de passer par l'interface ou d'utiliser l'url:

.. code-block:: bash

    /__da/less/compile/_all