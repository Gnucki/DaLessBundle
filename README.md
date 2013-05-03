DaLessBundle
============

The DaLessBundle is a Symfony2's bundle that allows to compile less files to css without having to install anything else but this bundle.

Installation
------------

Add the following line to the require section of you composer.json file:

``` json
	"da/less-bundle": "dev-master"
```
	
Run the composer update command:

``` bash
	php composer.phar update
```
	
Add the following line to your AppKernel.php file (it is highly recommended to add it in the dev section):

``` php
	$bundles[] = new Da\LessBundle\DaLessBundle();
```
	
Update the assets:

``` bash
	php app/console assets:install           # Windows
	php app/console assets:install --symlink # Others
```
	
As a security, you have to specify some roles that can access the less features in your config.yml file:

``` bash
	da_less:
		roles: [ROLE_ADMIN]
```
		
You can bypass this security like that (you should not do it in production environment):

``` bash
	da_less:
		roles: [anonymous]
```
		
You should now be able to use the DaLessBundle.

Access the interface of compilation
-----------------------------------

An interface allows to process the different available kinds of compilation:

* The compilation through a form
* The compilation through a configuration file 

It is available to the following url:

    /__da/less
	
Compilation through a form
--------------------------

It is possible to use a form to configure and execute a compilation.

### Definition of the parameters

The 4 configurable parameters are:

* **The default directory:**
  This is the path of the directory where you can find the less files that will be used during the compilation.

* **The override directory:**
  In the case where there are many less files in the default directory, you can define a path to an override directory 
  whom its files will override the files of the default directory. This is especially helpful when you have one configuration less file that you want in several versions, for instance.

* **The source file:**
  This is the name of the less source file of the compilation. If the default directory is not defined, you have to specify 
  the path of the file.

* **The destination file:**
  This is the name and the path of the css destination file.

### Format of the parameters

For obvious security reasons, it is not possible to access any file of the disk.
The source files must be in a directory of that format:

``` bash
    {bundle_root_dir}/Resources/private/less # for directories and less files.
    {bundle_root_dir}/Resources/public/css # for css files.
```
	
Of course, the files can be in any of the subdirectories of this directory.

A syntactic sugar has been implemented to avoid the tedious repetition of this path:

``` bash
    BundleName:path/to/directory/or/file
```
	
* **BundleName** represents the name of the bundle in Symfony (DaLessBundle for this bundle for instance).
* **path/to/directory/or/file** is the path to the directory or the file relative to the above defined directories.

Which gives, for the default and override directories:

``` bash
    MySuperBundle:themes/aqua
    # equivalent to the directory /src/My/SuperBundle/Resources/private/less/themes/aqua
    MySuperBundle:
    # equivalent to the directory /src/My/SuperBundle/Resources/private/less
```
	
And for the source and destination files:

``` bash
    MySuperBundle:themes/aqua/mystyle
    # equivalent to the file /src/My/SuperBundle/Resources/private/less/themes/aqua/mystyle.less for a source.
    # equivalent to the file /src/My/SuperBundle/Resources/public/css/themes/aqua/mystyle.css for a destination.
```
	
Compilation through a configuration file
----------------------------------------

It is possible to use a configuration file to configure compilations that you want to execute frequently.

### Format of the configuration

Configuration file:

``` bash
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
```
				
In this exemple, bootstrap and custom are identifiers of a compilation. Like for a compilation through a form, it is possible 
to use the simplified notation.

In the case of the compilation of identifier bootstrap, you have the files of the directory /src/Resources/private/less 
of the BootstrapBundle that will be overriden by the files of the directory /src/Resources/private/less/bootstrap 
of the MySuperBundle.
Bootstrap is a css library compiled from less files that you can customized by changing the values contained in the file variable.less.
You can imagine that a variables.less file in the directory /src/Resources/private/less/bootstrap of the MySuperBundle. 
The source file of the compilation is bootstrap.less (resulting of the merge of the default and override directories) 
and the destination file is /src/Resources/public/css/bootstrap.css.

It is possible to simplify the code when there is no override directory. Thus:

``` bash
    # /app/config/config.yml
    da_less:
        compilation:
            custom:
                default: "MySuperBundle:"
                override:
                source: custom
                destination: "MySuperBundle:custom"
```

is equivalent to:

``` bash
    # /app/config/config.yml
    da_less:
        compilation:
            custom:
                source: "MySuperBundle:custom"
                destination: "MySuperBundle:custom"
```

### Execution of a configurated compilation

To execute a configurated compilation, you just have to use the interface or the following url:

    /__da/less/compile/{compilation_id}

To execute all the configurated compilations, you just have to use the interface or the following url:

    /__da/less/compile/_all

Documentation
-------------

An english and a french documentation are present in the Resources/doc directory of the bundle.