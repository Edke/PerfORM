
PerfORM - PHP object-relation mapping based on David Grudl's Dibi layer & Nette Framework
========================================================================================

Description to be filled.



Features
--------

To be filled.



Requirements
------------

* PHP 5.2 and higher
* Nette Framework
* Dibi abstraction layer
* Support only for PostgeSQL database
* FSHL for syntax highlighting of SQL code
* memcached support is optional, but highly recommended



Instalation
-----------

This guide assumes that you have your Nette application with dibi layer working and that you have
basic knowledge about Nette application project structure. Steps to integrate PerfORM into your
Nette project may vary, this is the way I suggest to use:

 1. put PerfORM into your applications's libs/PerfORM folder
 2. put PerfORMConsoleModule into your app folder for console interface
 3. create folder bin in your project's root and create symlink bin/perform.php that points 
    to app/PerfORMConsoleModule/bootstrap.php
 4. (optional) for syntax highlighting in web interface or console download latest FSHL package 
    (http://code.google.com/p/fshl/downloads/list) and unpack to your libs folder
 5. (optional) create netterobots.txt in libs/fshl folder that contains: "Disallow: /fshl_cache"
 6. (optional) for syntax highlighting in console copy PerfORM/fshl/out/ANSI_UTF8_output.php into your 
    libs/fshl/out folder.
 7. (optional) for syntax highlighting in your web application (backend) use CSS style in
    PerfORM/Css/fshl_COHEN_style.css.
 8. add configuration to your application's config.ini:

    perform.memcache_host= localhost
    perform.memcache_port= 11211
    perform.cache_prefix= perform
    perform.advertisememcached= true
    perform.profiler = false
    perform.storage_profiler = false
    perform.modelCache = %appDir%/temp/PerfORM

 9. create folder app/temp/PerfORM as set in config and make it writable for webserver 
    or your user when cli management is used
10. clear RobotLoader cache
11. create base models for your application, put them anywhere in app folder
    (usualy in app/models, app/YourModule/models etc.)
12. run PerfORM management SQL tool's syncdb to create database structure and generate
    final model's classes in app/temp/PerfORM folder
13. use models in your application



Model examples
--------------


Model templates are stored in app/models or any other app/SomeModule/models folder. Model 
template's name has to be PerfORMModelName. Example:


<?php

/**
 * PerfORMDisk
 *
 * @author kraken
 * @abstract
 */
abstract class PerfORMDisk extends PerfORM {


    protected function setup() {
        $this->addCharField('sap', 20)->setNotNullable()->addUnique();
        $this->addForeignKeyField('disk', 'Model')->addIndex();
        $this->addForeignKeyField('skupina', 'Material')->addIndex();
        $this->addDecimalField('sirka', 4, 1)->setNotNullable();
        $this->addDecimalField('priemer', 15, 1)->setNotNullable();
        $this->addCharField('roztec', 15)->setNullable();
        $this->addCharField('off_set', 5)->setNullable();
        $this->addCharField('prevedenie', 35)->setNullable();
        $this->addCharField('rozmer_skrateny', 10)->setNotNullable()->setNullCallback('nullRozmerSkratenyCallback');
        $this->addCharField('rozmer_cely', 65)->setNotNullable()->setNullCallback('nullRozmerCelyCallback');
        $this->addSmallIntegerField('rating_pocet')->setNullable();
        $this->addDecimalField('rating_hodnotenie', 3, 2)->setNullable();
    }


    public function __toString() {
        return $this->rozmer_cely;
    }


    public function nullRozmerSkratenyCallback() {
        $priemer = (float) $this->priemer;
        return trim(preg_replace('#[,/\s]#', '', sprintf('%s%s', $priemer, $this->roztec)));
    }


    public function nullRozmerCelyCallback() {
        # 5.5x13 4/114,3 38
        return trim(preg_replace('#\s+#', ' ', sprintf('%sx%s %s %s', $this->sirka, $this->priemer,
                                $this->roztec, $this->off_set)));
    }
}
?>

When executing PerfORMController::syncdb (from console via PerfORMConsoleModule or from your admin application), 
final model classes will be created in app/temp/PerfORM and also database structure is inserted into your configured
database. These final classes are used in your application as models. Example of generated model class:

<?php
/**
 * Disk
 *
 * @final
 * @filesource app/models/PerfORMDisk.php
 * 
 * @property string $sap
 * @property integer $disk
 * @property integer $skupina
 * @property integer $sirka
 * @property integer $priemer
 * @property string $roztec
 * @property string $off_set
 * @property string $prevedenie
 * @property string $rozmer_skrateny
 * @property string $rozmer_cely
 * @property integer $rating_pocet
 * @property integer $rating_hodnotenie
 */
final class Disk extends PerfORMDisk {

    
    /**
     * Setup of BaseModel
     */
    protected function setup()
    {
	parent::setup();
	
    }


    /**
     * Returns last modification of model
     * @return integer
     */
    protected function getLastModification()
    {
	return '1276610205';
    }
}
?>

PerfORMController
-----------------


To generate model classes and syncronize your database structure, you need to use PerfORMController.
You can use console module as described in instalation or integrate it's methods into your app's 
backend.

PerfORMController::sqlall()
  Shows creates for tables, indexes for every your module in application.

PerfORMController::sqlclear($confirm = false)
  Drops models from database, without $confirm set to true return tasks to be executed.

PerfORMController::syncbd($confirm = false) 
  Executes creates and alters to synchronize your database structure with your pending model changes. Without
  $confirm set to true returns tasks to be executed.

PerfORMController::sqlset($confirm = false)
  Sets internal metadata database to state as if syncdb was successfully executed , but no database structure
  is modified. This can be used if you create your models and your database structure is already created.

All actions return SQL code to be performed or code that was executed (depends on $confirm).



Basic model manupulation
------------------------

This example shows loading and filling of models with use of foreign keys.


$tread = new Dezen();
if (!$tread->objects()->load('nazov=%s', 'AH11')) {

    $manufacturer = new Vyrobca();
    if (!$manufacturer->objects()->load('skratka=%s', 'HAN')) {
        $manufacturer->nazov = 'Hankook';
        $manufacturer->typ = 'pneu';
        $manufacturer->poradie = 2;
        $manufacturer->aktivny = true;
        $manufacturer->farba = 'FDC581';
        $manufacturer->skratka = 'HAN';
    }

    $tread->nazov = 'AH11';
    $tread->sezona = 'L';
    $tread->vyrobca = $manufacturer;
}

$tyre = new Pneumatika();
$tyre->sap = sprintf('HAN-30%05s', rand(1, 1000));
$tyre->dezen = $tread;
$tyre->sirka = 215;
$tyre->vyska = 75;
$tyre->priemer = 17.5;
$tyre->li = '126/124';
$tyre->si = 'M';
$tyre->prevedenie = '12PR';
$tyre->save();


Lazy loading
-------------

When tree of model gets complicated, working with whole model can slow things down. And mostly
we don't need whole model tree while interacting in view/template. Lazy loading comes handy in
these situtions, setting parts of tree not to be loaded by default. But they will be loaded
when needed later on.

$tyre = new Pneumatika();
$tyre->setLazyLoading(); //sets lazy loading for all foreign keys of model

$tyre->objects()->load('sap = %s', 'HAN-2000981');
echo $tyre->dezen->vyrobca; //when accessing foreign key dezen of model Pneumatika, it will load before accessing

Choices
-------

See model Dezen.


$tread = new Dezen();
$tread->objects()->load('nazov=%s', 'AH11');

echo $tread->sezona;
echo $tread->sezona->display();
echo $tread->sezona->getChoices();


Inheritance
-----------

See models PressRelease, TyreTest and Document.


$pressRelease= new PressRelease();
$pressRelease->title= 'Hankook Tire Announces 2010 Motorsports Sponsorships';
$pressRelease->content= 'LAS VEGAS, Nov. 5, 2009 - After celebrating its most successful motorsports ...';
$firstPressRelease= $pressRelease->save();


$test= new TyreTest();
$test->title= 'Nokian H “very recommendable” in ADAC tyre test';
$test->content= '“Very recommendable” is the Nokian H according to the “ADAC judgement” in the latest summer tyre test ...';
$test->rating= 95;
$test->save();


$pressRelease= new PressRelease();
$pressRelease->objects()->load('id=%i', $firstPressRelease);
echo $pressRelease->title;

$pressRelease->title= 'Hankook Tire Announces 2010 Motorsports Sponsorships (modified)';
$pressRelease->save();


$test= new TyreTest();
$result=$test->objects()
    ->where('rating > %i', 90)
    ->get();

foreach($result as $document)
{
    echo $document->id.'-'.$document->title;
    $document->title= "Nokian H “very recommendable” in ADAC tyre test (modified)";
    $document->rating= 97;
    $document->save();
}




License
-------

PerfORM will be released under open source licence



Documentation and Examples
--------------------------

To be filled.



Forum
-----

http://forum.nettephp.com/cs/3368-koncept-orm-postavenom-na-skvelom-dibi-a-inspirovanym-django-model


