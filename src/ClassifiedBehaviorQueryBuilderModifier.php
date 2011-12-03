<?php

/**
 * This file declare the ClassifiedBehaviorQueryBuilderModifier class.
 *
 * @copyright (c) Carpe Hora SARL 2011
 * @since 2011-11-25
 * @license     MIT License
 */

/**
 * @author Julien Muetton <julien_muetton@carpe-hora.com>
 * @package propel.generator.behavior.classified
 */
class ClassifiedBehaviorQueryBuilderModifier
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

  public function getActiveRecordClassname()
  {
    $table = $this->behavior->getTable();
    return $this->builder->getNewStubObjectBuilder($table)->getClassname();
  }

  public function getActiveQueryClassname()
  {
    $table = $this->behavior->getTable();
    return $this->builder->getNewStubQueryBuilder($table)->getClassname();
  }

  protected function getClassificationTable()
  {
    return $this->behavior->getClassificationTable();
  }

  protected function getClassificationLinkTable()
  {
    return $this->behavior->getClassificationLinkTable();
  }

  protected function getClassificationTableName()
  {
    return $this->behavior->getClassificationTableName();
  }

  public function getClassifiactionLinkTableName()
  {
    return $this->behavior->getClassificationLinkTableName();
  }

  public function getClassifiactionLinkTableCommonname()
  {
    return $this->behavior->getClassificationLinkTable()->getCommonname();
  }

  public function getClassificationActiveRecordClassname()
  {
    $classifiedTable = $this->getClassificationTable();
    return $this->builder->getNewStubObjectBuilder($classifiedTable)->getClassname();
  }

  public function getClassificationLinkActiveRecordClassname()
  {
    $classificationLinkTable = $this->getClassificationLinkTable();
    return $this->builder->getNewStubObjectBuilder($classificationLinkTable)->getClassname();
  }

  public function getClassificationActiveQueryClassname()
  {
    $classificationTable = $this->getClassificationTable();
    return $this->builder->getNewStubQueryBuilder($classificationTable)->getClassname();
  }

  public function getClassificationLinkActiveQueryClassname()
  {
    $classificationLinkTable = $this->getClassificationLinkTable();
    return $this->builder->getNewStubQueryBuilder($classificationLinkTable)->getClassname();
  }

  protected function getParameter($parameter)
  {
    return $this->behavior->getParameter($parameter);
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

  public function queryAttributes($builder)
  {
    $this->setBuilder($builder);

    return <<<EOF
/** required namespaces */
protected \$requiredNamespaces = array();

/** classification filters */
protected \$classificationFilters = array();
EOF;
  }

  public function queryMethods($builder)
  {
    $this->setBuilder($builder);

    $script = <<<EOF
/**
 * Adds a filter on classification for this query.
 * if several namespaces are provided, then assume this is ANY of the matches.
 *
 * // find *big* AND *blue* objects
 * ->filterByClassified('size', 'big');
 * ->filterByClassified('color', 'blue');
 *
 * // find *big* OR *blue* objects
 * ->filterByClassified(array(
 *        'size' => 'big',
 *        'color' => 'blue',));
 *
 * // filter *big* OR *small*
 * ->filterByClassified('size', array('big', 'small') 'OR', \$exclude_disclosed = true)
 *
 * @param String  \$namespace        classification \$namespace.
 * @param String  \$classification   classification name if namespace is provided.
 * @param String  \$operator         operator to use for search combination ('OR', AND', 'XOR').
 * @param Boolean \$paranoid         should the object be rejected if no matching at all for namespace. (exclude disclosed)
 * @return {$this->getActiveQueryClassname()}
 */
public function filterByClassified(\$namespace, \$classifications = null, \$operator = 'and', \$paranoid = true)
{
  \$uid = 'classified_'.uniqid();
  \$conditions = array();
  if (!is_null(\$classifications) &&
      (is_array(\$namespace) || (\$namespace instanceof PropelCollection))) {
    \$paranoid = \$operator;
    \$operator = \$classifications;
    \$classifications = null;
  }
  \$classifications = \$this->prepareClassifications(\$namespace, \$classifications);
  foreach (\$classifications as \$ns => \$classes) {
    \$cond = \$ns . '_' . \$uid;
    if (!\$paranoid) {
      \$this->conditionForDisclosed(\$ns, \$cond . '_paranoid');
      \$conditions[] = \$cond . '_paranoid';
    }

    // paranoid
    \$conditions[] = \$cond;
    \$this
        ->conditionForClassified(\$classes, \$operator, \$cond);
  }
  return \$this->where(\$conditions, 'OR');
}

/**
 * Adds a filter on disclosed content for this query.
 *
 * @param String  \$namespace        classification \$namespace.
 * @return {$this->getActiveQueryClassname()}
 */
public function filterByDisclosed(\$namespace)
{
  \$uid = 'disclosed_'.uniqid();

  return \$this
      ->conditionForDisclosed(\$namespace, \$uid)
      ->where(array(\$uid), 'AND');
}

/**
 * create condition to filter objects with not classification in \$namespace
 *
 * @param String  \$namespace      the namespace to use.
 * @param String  \$condition_name name to use for created condition.
 * @return {$this->getActiveQueryClassname()}
 */
protected function conditionForDisclosed(\$namespace, \$condition_name)
{
  \$namespace = {$this->peerClassname}::normalizeScopeName(\$namespace);
  \$alias = 'empty_ns_'.uniqid();
  \$table_name = \$this->getModelAliasOrName() === '{$this->getActiveRecordClassname()}'
                    ? '{$this->behavior->getTable()->getCommonname()}'
                    : \$this->getModelAliasOrName();
EOF;
      $pks = '';
      foreach($this->behavior->getTable()->getPrimaryKey() as $key => $column) {
        $pks .= " '. \$alias . '_link.". $this->behavior->getTable()->getCommonname() ."_". $column->getName() ."";
      }
      $script .=<<<EOF
  \$sql_condition = 'NOT EXISTS ('."\n".
        'SELECT {$pks} FROM {$this->getClassifiactionLinkTableCommonname()} '. \$alias . '_link '."\n".
        'JOIN {$this->getParameter('classification_table')} '. \$alias . ' '."\n".
        'ON ('. \$alias . '.id = '. \$alias . '_link.{$this->getParameter('classification_table')}_id '.
EOF;
    // @todo optimize to check id first
    foreach($this->behavior->getTable()->getPrimaryKey() as $key => $column) {
      $script .=<<<EOF
            'AND '. \$alias . '_link.{$this->behavior->getTable()->getCommonname()}_{$column->getName()} = ' . \$table_name . '.{$column->getName()} '."\n".
EOF;
    }
    $script .= <<<EOF
        ') '."\n".
        'WHERE ( '.
            \$alias .'.{$this->getParameter('scope_column')} = ? '."\n".
        ') GROUP BY {$pks}' .
        ')';

  return \$this->condition(\$condition_name, \$sql_condition, \$namespace, PDO::PARAM_STR);
}

/**
 * create condition for classified content filtering.
 *
 * @param {$this->getClassificationActiveRecordClassname()} \$classifications classification object, collection or array to filter against
 * @return {$this->getActiveQueryClassname()}
 */
protected function conditionForClassified(\$classifications, \$operator, \$cond_name)
{
  \$conditions = array();
  if(is_array(\$classifications) || \$classifications instanceof PropelCollection) {
    if (count(\$classifications) === 0) {
      return \$this;
    }
    if (count(\$classifications) > 1) {
      \$count  = 0;
      \$cond = 'classified_'.uniqid();
      foreach (\$classifications as \$classification) {
        \$conditions[] = \$cond . '_' . (++\$count);
        \$this->conditionForClassified(\$classification, \$operator, \$cond . '_' . \$count);
      }
      return \$this->combine(\$conditions, \$operator, \$cond_name);
    }

    \$classifications = array_pop(\$classifications);
  }


  \$alias = 'empty_ns_'.uniqid();
  \$table_name = \$this->getModelAliasOrName() === '{$this->getActiveRecordClassname()}'
                    ? '{$this->behavior->getTable()->getCommonname()}'
                    : \$this->getModelAliasOrName();
  \$query = {$this->getClassificationLinkActiveQueryClassname()}::create(\$alias)

EOF;
    foreach($this->behavior->getTable()->getPrimaryKey() as $key => $column) {
      $script .=<<<EOF
        ->addUsingOperator(new Criterion(\$this, null, \$alias.'.{$this->behavior->getTable()->getCommonname()}_{$column->getName()} = ' . \$table_name . '.{$column->getName()}', Criteria::CUSTOM), null, null)

EOF;
    }
    $script .= <<<EOF
  ;
EOF;
      foreach($this->getClassificationTable()->getPrimaryKey() as $key => $column) {
        $script .=<<<EOF
  \$conditions[] = \$alias . '.{$this->getClassificationTable()->getCommonname()}_{$column->getName()} = ' . \$classifications->get{$column->getPhpName()}();

EOF;
    }
      $script .=<<<EOF

  \$params = array();
  \$sql = BasePeer::createSelectSql(\$query, \$params);
  // replace SELECT statement to have * and alias
  //\$sql = preg_replace('#\\b{$this->getClassificationLinkTable()->getCommonname()}\.#', \$alias.'.', \$sql);
  \$sql_select = str_replace('SELECT  FROM ', 'SELECT * FROM {$this->getClassificationLinkTable()->getCommonname()} '.\$alias, \$sql);
  \$condition = 'EXISTS (' .
                  \$sql_select . ' ' ."\n".
                  'AND ('.join(sprintf(' %s ', \$operator), \$conditions) . ')' ."\n".
                ')';
  return \$this->condition(\$cond_name, \$condition);
}

/**
 * retrieve the classification list.
 * prepareClassifications(array('ns' => array('class1', 'class2', 'class3')))
 * prepareClassifications('ns', array('class1', 'class2', 'class3'))
 * prepareClassifications('ns', 'class1')
 *
 * @param String  \$namespace        classification \$namespace.
 * @param String  \$classification   classification name if namespace is provided.
 * @return array
 */
protected function prepareClassifications(\$namespace, \$classifications = null)
{
  return {$this->peerClassname}::prepareClassifications(\$namespace, \$classifications);
}
EOF;
    return $script;
  }
}
