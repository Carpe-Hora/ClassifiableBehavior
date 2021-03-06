<?php

/**
 * This file declare the ClassifiableBehaviorPeerBuilderModifier class.
 *
 * @copyright (c) Carpe Hora SARL 2011
 * @since 2011-11-25
 * @license     MIT License
 */

/**
 * @author Julien Muetton <julien_muetton@carpe-hora.com>
 * @package propel.generator.behavior.classifiable
 */
class ClassifiableBehaviorPeerBuilderModifier
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

    $string = <<<EOF
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

/**
 * retrieve the classification list.
 * {$this->peerClassname}::prepareClassifications(array('ns' => array('class1', 'class2', 'class3')))
 * {$this->peerClassname}::prepareClassifications('ns', array('class1', 'class2', 'class3'))
 * {$this->peerClassname}::prepareClassifications('ns', 'class1')
 *
 * @param String  \$namespace        classification \$namespace.
 * @param String  \$classification   classification name if namespace is provided.
 * @return array
 */
public static function prepareClassifications(\$namespace, \$classifications = null)
{
  \$ret = array();
  if (is_null(\$classifications)) {
    \$classifications = \$namespace;
    \$namespace = null;
  }
  if(!is_array(\$classifications) && !(\$classifications instanceof PropelCollection)) {
    \$classifications = array(\$classifications);
  }

  foreach (\$classifications as \$key => \$classification) {
    \$ns = is_null(\$namespace) ? \$key : \$namespace;
    if (\$classification instanceof {$this->getClassificationActiveRecordClassname()})
    {
      \$scope = \$classification->{$this->getGetterForClassificationColumnForParameter('scope_column')}();
      \$key = \$classification->{$this->getGetterForClassificationColumnForParameter('classification_column')}();
      if (isset(\$ret[\$scope])) {
        \$ret[\$scope][\$key] = \$classification;
      }
      else {
        \$ret[\$scope] = array(\$key => \$classification);
      }
    }
    elseif(is_array(\$classification) || (\$classification instanceof PropelCollection)) {
      \$classes = {$this->peerClassname}::prepareClassifications(\$ns, \$classification);
      foreach (\$classes as \$class) {
        foreach (\$class as \$c) {
          \$key = \$c->{$this->getGetterForClassificationColumnForParameter('classification_column')}();
          \$scope = \$c->{$this->getGetterForClassificationColumnForParameter('scope_column')}();
          if (isset(\$ret[\$scope])) {
            \$ret[\$scope][\$key] = \$c;
          }
          else {
            \$ret[\$scope] = array(\$key => \$c);
          }
        }
      }
    }
    else {
      // well retrieve it.
      // @todo optimize the retrieval...
      \$c = {$this->getClassificationActiveQueryClassname()}::create()
        ->{$this->getFilterByClassificationColumnForParameter('scope_column')}({$this->peerClassname}::normalizeScopeName(\$ns))
        ->{$this->getFilterByClassificationColumnForParameter('classification_column')}({$this->peerClassname}::normalizeClassificationName(\$classification))
        ->findOne();
      if (!\$c) {
        throw new Exception(sprintf('Unknown category %s/%s', \$ns, \$classification));
      }

      \$key = \$c->{$this->getGetterForClassificationColumnForParameter('classification_column')}();
      \$scope = \$c->{$this->getGetterForClassificationColumnForParameter('scope_column')}();
      if (isset(\$ret[\$scope])) {
        \$ret[\$scope][\$key] = \$c;
      }
      else {
        \$ret[\$scope] = array(\$key => \$c);
      }
    }
  }

  return \$ret;
}
EOF;
    return $string;
  }
}

