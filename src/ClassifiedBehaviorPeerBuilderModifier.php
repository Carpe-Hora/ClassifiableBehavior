<?php

/**
 * This file declare the ClassifiedBehaviorPeerBuilderModifier class.
 *
 * @copyright (c) Carpe Hora SARL 2011
 * @since 2011-11-25
 * @license     MIT License
 */

/**
 * @author Julien Muetton <julien_muetton@carpe-hora.com>
 * @package propel.generator.behavior.classified
 */
class ClassifiedBehaviorPeerBuilderModifier
{
  protected $behavior, $table, $builder, $objectClassname, $peerClassname;

  public function __construct($behavior)
  {
    $this->behavior = $behavior;
    $this->table = $behavior->getTable();
  }

	protected function setBuilder($builder)
	{
		$this->builder = $builder;
		$this->objectClassname = $builder->getStubObjectBuilder()->getClassname();
		$this->queryClassname = $builder->getStubQueryBuilder()->getClassname();
		$this->peerClassname = $builder->getStubPeerBuilder()->getClassname();
	}

  protected function getParameter($key)
  {
    return $this->behavior->getParameter($key);
  }

  protected function getClassificationTable()
  {
    return $this->behavior->getClassificationTable();
  }

  protected function getClassificationTableName()
  {
    return $this->behavior->getClassificationTableName();
  }

  public function getClassificationActiveRecordClassname()
  {
    $classificationTable = $this->getClassificationTable();
    return $this->builder->getNewStubObjectBuilder($classificationTable)->getClassname();
  }

  public function getClassificationActiveQueryClassname()
  {
    $classificationTable = $this->getClassificationTable();
    return $this->builder->getNewStubQueryBuilder($classificationTable)->getClassname();
  }


  protected function getClassificationColumnForParameter($parameter)
  {
    return $this->behavior->getClassificationColumnForParameter($parameter);
  }

  protected function getOrderByClassificationColumnForParameter($parameter)
  {
    return $this->behavior->getOrderByClassificationColumnForParameter($parameter);
  }

  protected function getFilterByClassificationColumnForParameter($parameter)
  {
    return $this->behavior->getFilterByClassificationColumnForParameter($parameter);
  }

  protected function getGetterForClassificationColumnForParameter($parameter)
  {
    return $this->behavior->getGetterForClassificationColumnForParameter($parameter);
  }

  protected function getSetterForClassificationColumnForParameter($parameter)
  {
    return $this->behavior->getSetterForClassificationColumnForParameter($parameter);
  }

  public function staticMethods($builder)
  {
    $this->setbuilder($builder);

    return <<<EOF
/**
 * normalize scope name.
 *
 * @param String \$scope scope to normalize
 * @return String
 */
public static function normalizeScopeName(\$scope)
{
  return \$scope;
}

/**
 * normalize classification name.
 *
 * @param String \$classification classification to normalize
 * @return String
 */
public static function normalizeClassificationName(\$classification)
{
  return \$classification;
}
EOF;
  }
}

