ClassifiedBehavior
==================

Quick start
-----------

Simply declare the behavior for the classified objects to allow filtering by classifications:

``` xml
<database name="propel">
  <table name="picture">
    <column name="id" type="INTEGER" required="true" primaryKey="true" autoIncrement="true" />
    <column name="name" type="VARCHAR" size="255" />
    <behavior name="classified" />
  </table>
</database>
```

then just add classificationements to your object:

``` php
<?php
// create your objects...
$MyPhpRepo = new Repository();
$MyPhpRepo->setName('my php repository');
$MyPhpRepo->classify('visibility', 'public');
$MyPhpRepo->classify('language', 'php');
$MyPhpRepo->classify('language', 'js');
$MyPhpRepo->classify('license', 'MIT');

$MyJsRepo = new Repository();
$MyJsRepo->setName('my php repository');
$MyJsRepo->classify('language', 'js');
$MyJsRepo->classify('language', 'php'); // is now classified in js AND php
$MyJsRepo->classify('license', 'GPL');
$MyJsRepo->disclose('language', 'js'); // now only classified in php


// ...and then retrieve it
RepositoryQuery::create()
  // filter for 'php' AND 'js' language, paranoid
  ->filterByClassified('language', array('php', 'js'), 'and', $paranoid = true)
  // filter for 'MIT'OR 'GPL' license, paranoid
  ->filterByClassified('license', array('MIT', 'BSD'), 'or', $paranoid = true)
  // filter for public visibility,
  // default is public, so request match if no visibility defined
  ->filterByClassified('visibility', 'public', null, $paranoid = false);
```

Previous example will return a `PropelCollection` with ```$MyPhpRepo``` in it.

`OR` searches are easily done by giving an array and the operator to use for subqueries:

``` php
<?php
RepositoryQuery::create()
  // filter by OR queries
  ->filterByClassified(array(
          'license' => array('MIT', 'BSD'), // licensed under MIT AND BSD as defined $operator argument
          'visibility' => 'public',         // or with public visibility
        ), $operator = 'AND', $paranoid = true)
```

To retrieve classifications an object is attached to, just call the ```getClassification``` method:

``` php
<?php
$myRepo->classify(array(
  'license' => array('MIT', 'BSD'),
  'visibility' => 'public'
), 'AND', $excludeUnclassified = true);
// return $myRepo

$myRepo->getClassification();
// array(
//  'license' => array('MIT', 'BSD'),
//  'visibility' => 'public'
// )

$myRepo->isClassified(array(
  'license' => array('MIT', 'BSD'),
  'visibility' => 'public'
), 'AND', $excludeUnclassified = true);
// return true

$myRepo->disclose();
```

To ease classifications management, behavior uses a unique classification table shared
for all classified content. To use separate tables, just override the
```classification_table``` parameter.

Usage
-----

You can use ClassifiedBehavior:

* limit access to classified content
* to organize collections and ease filtering
* to tag objects
* *put your idea here*

Installation
------------

Under the hood
--------------

Behavior creates a cross reference table to access classification dictionnary and filters using EXISTS statements.

Advanced configuration
----------------------

Following parameters are available :

* ```classification_table```: table to use to handle classifications.
* ```classification_column```: classification column to use.
* ```scope_column```: scope column to use.
* ```auto_create_classification```: if no classification found for parameters, should it be created ?
* ```scope_default```: comma separated default scope list.
* ```scope_matching```: how to match on scope ?
  * **strict**: namespace has to be strictly equal
  * **nested**: namespace has to start the same. separator is defined as ```nesting_separator```
  * **none**: no namespace check
* ```nesting_separator```: separator to use on nested scopes

### Scopes

A scope can be used to organize classifications. You can use namespaced scope by
providing a ```nesting_separator```. In such case, you can organize your
classifications as files and folders and search can be done on all the scope
descendants.

A scope can either be a foreign key to an object.

TODO
----

* AR disclose method
* nesting namespaces
* Index classification table fields
