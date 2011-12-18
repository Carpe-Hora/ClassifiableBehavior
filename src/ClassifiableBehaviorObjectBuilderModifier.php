<?php

/**
 * This file declare the ClassifiableBehaviorObjectBuilderModifier class.
 *
 * @copyright (c) Carpe Hora SARL 2011
 * @since 2011-11-25
 * @license     MIT License
 */

/**
 * @author Julien Muetton <julien_muetton@carpe-hora.com>
 * @package propel.generator.behavior.classifiable
 */
class ClassifiableBehaviorObjectBuilderModifier
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

  public function getClassificationLinkActiveRecordClassname()
  {
    $linkTable = $this->behavior->getClassificationLinkTable();
    return $this->builder->getNewStubObjectBuilder($linkTable)->getClassname();
  }

  public function getClassificationLinkActiveQueryClassname()
  {
    $linkTable = $this->behavior->getClassificationLinkTable();
    return $this->builder->getNewStubQueryBuilder($linkTable)->getClassname();
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

  public function getClassificationPeerClassname()
  {
    $classificationTable = $this->getClassificationTable();
    return $this->builder->getNewStubPeerBuilder($classificationTable)->getClassname();
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

  protected function getGetterForClassificationLink()
  {
    return sprintf('get%ss', $this->getClassificationLinkActiveRecordClassname());
  }

  protected function getGetterForClassification()
  {
    return sprintf('get%s', $this->getClassificationActiveRecordClassname());
  }

  protected function getGetterForClassificationCollection()
  {
    return sprintf('get%ss', $this->getClassificationActiveRecordClassname());
  }

  protected function getClearForClassificationCollection()
  {
    return sprintf('clear%ss', $this->getClassificationActiveRecordClassname());
  }

  protected function getInitForClassificationCollection()
  {
    return sprintf('init%ss', $this->getClassificationActiveRecordClassname());
  }

  protected function getSetterForClassificationCollection()
  {
    return sprintf('set%ss', $this->getClassificationActiveRecordClassname());
  }

  protected function getAddClassificationMethod()
  {
    return sprintf('add%s', $this->getClassificationActiveRecordClassname());
  }

  public function objectMethods($builder)
  {
    $this->setbuilder($builder);
    $cheduleForDeletionProperty = sprintf('%ssScheduledForDeletion', lcfirst($this->getClassificationActiveRecordClassname()));

    return <<<EOF
/**
 * classify an object.
 *
 * \$object->classify('foo', 'bar');
 * \$object->classify(array(
 *      'foo' => array('bar', 'foo_bar')
 *    ));
 * \$object->classify(array(
 *      'foo' => array('bar', 'foo_bar'),
 *      'baz' => 'caz'
 *    ));
 *
 * @param String  \$namespace        classification \$namespace.
 * @param String  \$classification   classification name if namespace is provided.
 * @return {\$this->objectClassname}
 */
public function classify(\$namespace, \$classification = null, \$con = null)
{
  if (is_null(\$con) && \$classification instanceof PropelPDO) {
    \$con = \$classification;
    \$classification = null;
  }

  \$classifications = \$this->prepareClassifications(\$namespace, \$classification);
  \$mine = \$this->prepareClassifications(\$this->{$this->getGetterForClassificationCollection()}());
  foreach (\$classifications as \$ns => \$classes) {
    \$diff = isset(\$mine[\$ns]) ?  array_diff(\$classes, \$mine[\$ns]) : \$classes;
    \$new = array_intersect(\$classes, \$diff);
    foreach (\$new as \$classification) {
      \$this->{$this->getAddClassificationMethod()}(\$classification, \$con);
    }
  }
  return \$this;
}

/**
 * disclose an object.
 *
 * @param String  \$namespace        classification \$namespace.
 * @param String  \$classification   classification name if namespace is provided.
 * @return {$this->objectClassname}
 */
public function disclose(\$namespace = null, \$classification = null)
{
  // disclose all ?
  if (is_null(\$namespace)) {
    \$this->{$cheduleForDeletionProperty} = \$this->{$this->getGetterForClassificationLink()}();
    \$this->{$this->getInitForClassificationCollection()}();

    return \$this;
  }

  // disclose on a namespace
  if (is_null(\$classification) && is_string(\$namespace)) {
    \$namespace = {$this->peerClassname}::normalizeScopeName(\$namespace);
    \$toRemove = array();
    foreach (\$this->{$this->getGetterForClassificationLink()}() as \$link) {
      if (\$namespace === \$link->{$this->getGetterForClassification()}()->{$this->getGetterForClassificationColumnForParameter('scope_column')}()) {
        \$toRemove[] = \$link;
      }
    }

    \$this->doRemoveClassificationLinks(\$toRemove);

    return \$this;
  }

  // list of classification items ?
  if (\$namespace instanceof PropelCollection) {
    throw new PropelException('please use "{$this->getSetterForClassificationCollection()}" to manage collections');
  }

  // case prepareClassification compatible arguments
  \$classifications = \$this->prepareClassifications(\$namespace, \$classification);

  // retrieve links
  foreach (\$this->{$this->getGetterForClassificationLink()}() as \$link) {
    \$ns = \$link->{$this->getGetterForClassification()}()->{$this->getGetterForClassificationColumnForParameter('scope_column')}();
    \$val = \$link->{$this->getGetterForClassification()}()->{$this->getGetterForClassificationColumnForParameter('classification_column')}();
    if (  // namespace
          isset(\$classifications[\$ns]) &&
          // value
          in_array(\$val, \$classifications[\$ns])) {
      \$toRemove[] = \$link;
    }
  }

    // remove it
  \$this->doRemoveClassificationLinks(\$toRemove);

  return \$this;
}

/**
 * actualy remove classifications from current instance.
 */
protected function doRemoveClassificationLinks(\$classificationLinks)
{
  // transform into collection
  if (!(\$classificationLinks instanceof PropelCollection)) {
    \$collection = new PropelObjectCollection();
    \$collection->setModel("{$this->getClassificationLinkActiveRecordClassname()}");
    \$collection->setData(\$classificationLinks);
    \$classificationLinks = \$collection;
  }

  \$classificationLinkCollection = \$this->{$this->getGetterForClassificationLink()}();
  \$classificationCollection = \$this->{$this->getGetterForClassificationCollection()}();
  // easy way to access collection index
  \$link_array = array();
  \$class_array = array();
  foreach (\$classificationLinkCollection as \$index => \$c) {
    \$link_array[\$index] = \$c->hashCode();
  }
  foreach (\$classificationCollection as \$index => \$c) {
    \$class_array[\$index] = \$c->hashCode();
  }

  // prepare for deletion collection
  if (is_null(\$this->{$cheduleForDeletionProperty})) {
    \$this->{$cheduleForDeletionProperty} = \$classificationLinks;
  }
  else {
    foreach (\$classificationLinks->diff(\$this->{$cheduleForDeletionProperty}) as \$removeClass) {
      \$this->{$cheduleForDeletionProperty}->append(\$removeClass);
    }
  }

  foreach (\$classificationLinks as \$removeClass) {
    // in link collection
    if (false !== (\$index = array_search(\$removeClass->hashCode(), \$link_array))) {
      \$classificationLinkCollection->remove(\$index);
      unset(\$link_array[\$index]);
    }
    // in classification collection
    if (false !== (\$index = array_search(\$removeClass->{$this->getGetterForClassification()}()->hashCode(), \$class_array))) {
      \$classificationCollection->remove(\$index);
      unset(\$class_array[\$index]);
    }
  }
}

/**
 * retrieve and order the {$this->objectClassname} {$this->getClassificationTable()->getPhpName()} records
 */
public function getClassification(\$namespace = null)
{
  \$classifications = \$this->prepareClassifications(\$this->{$this->getGetterForClassificationCollection()}());

  if (is_null(\$namespace)) {
    return \$classifications;
  }
  if (isset(\$classifications[\$namespace])) {
    return \$classifications[\$namespace];
  }
  // @todo add default classification
  return \$default = array();
}

/**
 * Check wether the object match given classification.
 *
 * @param String  \$namespace        classification \$namespace.
 * @param String  \$classification   classification name if namespace is provided.
 * @param String  \$operator         operator to use for search combination ('OR', AND', 'XOR').
 * @param Boolean \$paranoid         should the object be rejected if no matching at all for namespace. (exclude disclosed)
 * @return array
 */
public function isClassified(\$namespace, \$classifications = null, \$operator = 'and', \$paranoid = true)
{
  if (!is_null(\$classifications) &&
      (is_array(\$namespace) || (\$namespace instanceof PropelCollection))) {
    \$paranoid = \$operator;
    \$operator = \$classifications;
    \$classifications = null;
  }

  \$classifications = \$this->prepareClassifications(\$namespace, \$classifications);
  \$mine = \$this->getClassification();
  foreach (\$classifications as \$ns => \$classes) {
    if(!isset(\$mine[\$ns])) {
      if (\$paranoid) {
        return false;
      }
      continue;
    }

    switch (strtolower(trim(\$operator)))
    {
      case 'and':
        if (count(\$mine[\$ns]) < count(\$classes) || // objects has not enough permission
            count(\$classes) !== count(array_intersect(\$mine[\$ns], \$classes)) // or common values are not as many as expected
        ) {
          return false;
        }
        break;
      case 'or':
        if (!count(array_intersect(\$mine[\$ns], \$classes)) ) {
          return false;
        }
        break;
      case 'xor':
        if (count(array_intersect(\$mine[\$ns], \$classes)) > 1) {
          return false;
        }
        break;
      default:
        throw new PropelExeption('unknown operator : ' . \$operator);
    }
  }
  return true;
}

/**
 * Retrun true if no classification found in \$namespace.
 *
 * @param String  \$namespace        classification \$namespace.
 */
public function isDisclosed(\$namespace = null)
{
  return 0 === count(\$this->getClassification(\$namespace));
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
  }
}
