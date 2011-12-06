<?php
/**
 * This file declare the ClassifiableBehavior class.
 *
 * @package propel.generator.behavior.classifiable
 * @author Julien Muetton <julien_muetton@carpe-hora.com>
 * @copyright (c) Carpe Hora SARL 2011
 * @since 2011-11-30
 */

require_once __DIR__ . '/ClassifiableBehaviorObjectBuilderModifier.php';
require_once __DIR__ . '/ClassifiableBehaviorQueryBuilderModifier.php';
require_once __DIR__ . '/ClassifiableBehaviorPeerBuilderModifier.php';

/**
 * define classifiableity level
 */
class ClassifiableBehavior extends Behavior
{
  protected $classificationTable, $objectBuilderModifier,
            $classificationLinkTable, $queryBuilderModifier,
            $peerBuilderModifier;

  protected $parameters = array(
    'classification_table' => 'classification',
    'classification_column' => 'classification',
		'scope_column' => 'scope',
    'auto_create_classification' => 'true',
		'scope_default' => '',
    'scope_matching' => 'nested',
    'nesting_separator' => '.',
  );

  public function modifyDatabase()
  {
    foreach ($this->getDatabase()->getTables() as $table) {
      if ($table->hasBehavior($this->getName())) {
        // don't add the same behavior twice
        continue;
      }
      if (property_exists($table, 'isClassificationLinkTable')) {
        // don't add the behavior to classification talbe
        continue;
      }
      if (property_exists($table, 'isClassificationTable') ||
        $this->getParameter('classification_table') === $table->getName()) {
        // don't add the behavior to classification talbe
        continue;
      }
      $b = clone $this;
      $table->addBehavior($b);
    }
  }

  public function modifyTable()
  {
    $this->addClassificationTable();
    $this->addClassificationLinkTable();
  }

  public function addClassificationTable()
  {
    $table = $this->getTable();
    $database = $table->getDatabase();
    $classificationTableName = $this->getParameter('classification_table');

    if (!$database->hasTable($classificationTableName))
    {
      $classificationTable = $database->addTable(array(
        'name'    => $classificationTableName,
        'package'   => $table->getPackage(),
        'schema'  => $table->getSchema(),
        'namespace' => $table->getNamespace() ? '\\' . $table->getNamespace() : null,
      ));

      $classificationTable->isClassificationTable = true;
      $pk = $classificationTable->addColumn(array(
        'name'					=> 'id',
        'autoIncrement' => 'true',
        'type'					=> 'INTEGER',
        'primaryKey'  => 'true'
      ));
      $pk->setNotNull(true);
      $pk->setPrimaryKey(true);

      $classificationTable->addColumn(array(
          'name' => $this->getParameter('classification_column'),
          'type' => 'VARCHAR',
          'size' => '50',
          'primaryString' => 'true'
      ));
      $classificationTable->addColumn(array(
          'name' => $this->getParameter('scope_column'),
          'type' => 'VARCHAR',
          'size' => '255'
      ));
/*      $classificationTable->addIndex(array('classification'));
      $classificationTable->addIndex(array('scope_column'));
*/
      $this->classificationTable = $classificationTable;
    }
    else {
      $this->classificationTable = $database->getTable($classificationTableName);
      $this->classificationTable->isClassificationTable = true;
    }
  }

  public function addClassificationLinkTable()
  {
    $table = $this->getTable();
    $database = $table->getDatabase();
    $classificationLinkTableName = $this->computeClassificationLinkTableName();

    if (!$database->hasTable($classificationLinkTableName))
    {
      $classificationLinkTable = $database->addTable(array(
        'name'    => $classificationLinkTableName,
        'package'   => $table->getPackage(),
        'schema'  => $table->getSchema(),
        'namespace' => $table->getNamespace() ? '\\' . $table->getNamespace() : null,
      ));
      $classificationLinkTable->setIsCrossRef(true);

      $classificationLinkTable->isClassificationLinkTable = true;
      $pk = $classificationLinkTable->addColumn(array(
        'name'					=> 'id',
        'autoIncrement' => 'true',
        'type'					=> 'INTEGER',
        'primaryKey'  => 'true'
      ));
      $pk->setNotNull(true);
      $pk->setPrimaryKey(true);

			// create the foreign key to this table
			$fk = new ForeignKey();
			$fk->setForeignTableCommonName($table->getCommonName());
			$fk->setForeignSchemaName($table->getSchema());
			$fk->setOnDelete('CASCADE');
			$fk->setOnUpdate(null);
			$tablePKs = $table->getPrimaryKey();
			foreach ($tablePKs as $key => $column) {
        $col = $classificationLinkTable->addColumn(array(
          'name' => $table->getCommonname().'_'. $column->getName(),
          'type' => $column->getType(),
          'size' => $column->getSize()
        ));
				$fk->addReference($col, $column);
			}
			$classificationLinkTable->addForeignKey($fk);

			// create the foreign key to classifiable content
			$fk = new ForeignKey();
			$fk->setForeignTableCommonName($this->classificationTable->getCommonName());
			$fk->setForeignSchemaName($this->classificationTable->getSchema());
			$fk->setOnDelete('CASCADE');
			$fk->setOnUpdate(null);
			$classificationTablePKs = $this->classificationTable->getPrimaryKey();
			foreach ($classificationTablePKs as $key => $column) {
        $col = $classificationLinkTable->addColumn(array(
          'name' => $this->getParameter('classification_table').'_'. $column->getName(),
          'type' => $column->getType(),
          'size' => $column->getSize()
        ));
				$fk->addReference($col, $column);
			}
			$classificationLinkTable->addForeignKey($fk);

      $this->classificationLinkTable = $classificationLinkTable;
    }
    else {
      $this->classificationLinkTable = $database->getTable($classificationLinkTableName);
      $this->classificationLinkTable->isClassificationLinkTable = true;
    }
  }

  protected function computeClassificationLinkTableName()
  {
    return sprintf('link_%s_%s',
                      $this->getTable()->getName(),
                      $this->classificationTable->getName()
                  );
  }

  /* builder shortcuts */

  public function getClassificationLinkTable()
  {
    return $this->classificationLinkTable;
  }

  public function getClassificationTable()
  {
    return $this->classificationTable;
  }

  public function getClassificationTableName()
  {
    return $this->classificationTable->getPhpName();
  }

  public function getClassificationLinkTableName()
  {
    return $this->classificationLinkTable->getPhpName();
  }

  public function getClassificationColumnForParameter($parameter)
  {
    return $this->classificationTable->getColumn($this->getParameter($parameter));
  }

  public function getOrderByClassificationColumnForParameter($parameter)
  {
    return sprintf('orderBy%s', ucfirst($this->getClassificationColumnForParameter($parameter)->getPhpName()));
  }

  public function getFilterByClassificationColumnForParameter($parameter)
  {
    return sprintf('filterBy%s', ucfirst($this->getClassificationColumnForParameter($parameter)->getPhpName()));
  }

  public function getGetterForClassificationColumnForParameter($parameter)
  {
    return sprintf('get%s', ucfirst($this->getClassificationColumnForParameter($parameter)->getPhpName()));
  }

  public function getSetterForClassificationColumnForParameter($parameter)
  {
    return sprintf('set%s', ucfirst($this->getClassificationColumnForParameter($parameter)->getPhpName()));
  }

  /* define builders */

	public function getObjectBuilderModifier()
	{
		if (is_null($this->objectBuilderModifier))
		{
			$this->objectBuilderModifier = new ClassifiableBehaviorObjectBuilderModifier($this);
		}
		return $this->objectBuilderModifier;
	}

	public function getQueryBuilderModifier()
	{
		if (is_null($this->queryBuilderModifier))
		{
			$this->queryBuilderModifier = new ClassifiableBehaviorQueryBuilderModifier($this);
		}
		return $this->queryBuilderModifier;
	}

	public function getPeerBuilderModifier()
	{
		if (is_null($this->peerBuilderModifier))
		{
			$this->peerBuilderModifier = new ClassifiableBehaviorPeerBuilderModifier($this);
		}
		return $this->peerBuilderModifier;
	}
} // END OF ClassifiableBehavior
