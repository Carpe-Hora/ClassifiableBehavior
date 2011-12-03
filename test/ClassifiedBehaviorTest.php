<?php
/*
 *	$Id: VersionableBehaviorTest.php 1460 2010-01-17 22:36:48Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

$_SERVER['PROPEL_DIR'] = dirname(__FILE__) . '/../../../../plugins/sfPropelORMPlugin/lib/vendor/propel/';
$propel_dir = isset($_SERVER['PROPEL_DIR']) ? $_SERVER['PROPEL_DIR'] : dirname(__FILE__) . '/../../../../../plugins/sfPropelORMPlugin/lib/vendor/propel/';
$behavior_dir = file_exists(__DIR__ . '/../src/')
                    ? __DIR__ . '/../src'
                    : $propel_dir . '/generator/lib/behavior/classified';

require_once $propel_dir . '/runtime/lib/Propel.php';
require_once $propel_dir . '/generator/lib/util/PropelQuickBuilder.php';
require_once $propel_dir . '/generator/lib/util/PropelPHPParser.php';
require_once $behavior_dir . '/ClassifiedBehavior.php';

/**
 * Test for ClassifiedBehavior
 *
 * @author     Julien Muetton
 * @version    $Revision$
 * @package    generator.behavior.classified
 */
class ClassifiedBehaviorTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
  	if (!class_exists('ClassifiedBehaviorTest1')) {
      $schema = <<<EOF
<database name="classified_behavior_test_applied_on_table">
  <table name="classified_behavior_test_1">
    <column name="id" type="INTEGER" primaryKey="true" autoincrement="true" />
    <column name="name" type="VARCHAR" size="255" />
    <behavior name="classified" />
  </table>
</database>
EOF;
			PropelQuickBuilder::buildSchema($schema);
      $class = array();
      $classifications = array(
        'size'    => array('big', 'small', 'medium'),
        'license' => array('MIT', 'GPL', 'BSD', 'commercial', 'creative common'),
        'format'  => array('jpg', 'png', 'bmp'),
        'audience'  => array('kid', 'adult'),
      );

      foreach($classifications as $scope => $values) {
        foreach ($values as $value) {
          $c = new Classification();
          $c->setScope($scope);
          $c->setClassification($value);
          $c->save();
          $class[$scope][$value] = $c;
        }
      }

      $teddy = new ClassifiedBehaviorTest1();
      $teddy->setName('Teddy');
      $teddy->addClassification($class['size']['small']);
      $teddy->addClassification($class['license']['creative common']);
      $teddy->addClassification($class['format']['jpg']);
      $teddy->save();

      $hotPic = new ClassifiedBehaviorTest1();
      $hotPic->setName('Hot pic !');
      $hotPic->addClassification($class['size']['medium']);
      $hotPic->addClassification($class['license']['commercial']);
      $hotPic->addClassification($class['audience']['adult']);
      $hotPic->save();
    }
  }

  public function testActiveQueryMethodsExtists()
  {
    $this->assertTrue(method_exists('ClassifiedBehaviorTest1Query', 'filterByClassified'));
    $this->assertTrue(method_exists('ClassifiedBehaviorTest1Query', 'conditionForEmptyScope'));
    $this->assertTrue(method_exists('ClassifiedBehaviorTest1Query', 'conditionForClassified'));
  }

  public function testActiveRecordMethodsExtists()
  {
    $this->assertTrue(method_exists('ClassifiedBehaviorTest1', 'classify'));
    $this->assertTrue(method_exists('ClassifiedBehaviorTest1', 'getClassifications'));
  }

  public function testClassificationParanoidQuery()
  {
    $class = array(
      'audience' => array(
          'adult' => ClassificationQuery::create()->filterByScope('audience')->filterByClassification('adult')->findOne(),
      )
    );
    $paranoidAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified('audience', $class['audience']['adult'], true, 'and')
      ->orderByName()
      ->find();

    $this->assertEquals(1, count($paranoidAdultPics));
    $this->assertEquals('Hot pic !', $paranoidAdultPics[0]->getName());
  }

  public function testClassificationDisclosedQuery()
  {
    $class = array(
      'audience' => array(
          'adult' => ClassificationQuery::create()->filterByScope('audience')->filterByClassification('adult')->findOne(),
      )
    );

    $disclosedAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified('audience', $class['audience']['adult'], false, 'and')
      ->orderByName()
      ->find();

    $this->assertEquals(2, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());
    $this->assertEquals('Teddy', $disclosedAdultPics[1]->getName());
  }

  public function testFilterByDisclosed()
  {
    $disclosedPublicPics = ClassifiedBehaviorTest1Query::create()
      ->filterByDisclosed('audience')
      ->orderByName()
      ->find();

    $this->assertEquals(1, count($disclosedPublicPics));
    $this->assertEquals('Teddy', $disclosedPublicPics[0]->getName());
  }

  public function testFilterOnSeveralClassifications()
  {
    $class = array(
      'audience' => array(
          'adult' => ClassificationQuery::create()->filterByScope('audience')->filterByClassification('adult')->findOne(),),
      'size' => array(
          'small' => ClassificationQuery::create()->filterByScope('size')->filterByClassification('small')->findOne(),
          'medium' => ClassificationQuery::create()->filterByScope('size')->filterByClassification('medium')->findOne(),
          'big' => ClassificationQuery::create()->filterByScope('size')->filterByClassification('big')->findOne(),),
    );

    $disclosedAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified('audience', $class['audience']['adult'], false, 'and')
      ->filterByClassified('size', $class['size']['medium'], false, 'and')
      ->orderByName()
      ->find();

    $this->assertEquals(1, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());

    $disclosedAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified('audience', $class['audience']['adult'], false, 'and')
      ->filterByClassified('size', array($class['size']['medium'], $class['size']['small']), false, 'or')
      ->orderByName()
      ->find();

    $this->assertEquals(2, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());
    $this->assertEquals('Teddy', $disclosedAdultPics[1]->getName());

    $disclosedAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified('audience', $class['audience']['adult'], false, 'and')
      ->filterByClassified('size', array($class['size']['medium'], $class['size']['small']), false, 'and')
      ->orderByName()
      ->find();

    $this->assertEquals(0, count($disclosedAdultPics));
  }
}
