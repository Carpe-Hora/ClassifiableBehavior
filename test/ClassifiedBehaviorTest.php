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
      $teddy->addClassification($class['license']['MIT']);
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
    $this->assertTrue(method_exists('ClassifiedBehaviorTest1Query', 'conditionForDisclosed'));
    $this->assertTrue(method_exists('ClassifiedBehaviorTest1Query', 'conditionForClassified'));
  }

  public function testActiveRecordMethodsExtists()
  {
  $this->markTestSkipped();
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
      ->filterByClassified('audience', $class['audience']['adult'], 'and', true)
      ->orderByName()
      ->find();

    $this->assertEquals(1, count($paranoidAdultPics));
    $this->assertEquals('Hot pic !', $paranoidAdultPics[0]->getName());

    $licensed = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified('license', array('MIT', 'creative common'), 'and', true)
      ->find();

    $this->assertEquals(1, count($licensed));
    $this->assertEquals('Teddy', $licensed[0]->getName());
  }

  public function testClassificationDisclosedQuery()
  {
    $class = array(
      'audience' => array(
          'adult' => ClassificationQuery::create()->filterByScope('audience')->filterByClassification('adult')->findOne(),
      )
    );

    $disclosedAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified('audience', $class['audience']['adult'], 'and', false)
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
      ->filterByClassified('audience', $class['audience']['adult'], 'and', false)
      ->filterByClassified('size', $class['size']['medium'], 'and', false)
      ->orderByName()
      ->find();

    $this->assertEquals(1, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());

    $disclosedAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified('audience', $class['audience']['adult'], 'and', false)
      ->filterByClassified('size', array($class['size']['medium'], $class['size']['small']), 'or', false)
      ->orderByName()
      ->find();

    $this->assertEquals(2, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());
    $this->assertEquals('Teddy', $disclosedAdultPics[1]->getName());

    $disclosedAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified('audience', $class['audience']['adult'], 'and', false)
      ->filterByClassified('size', array($class['size']['medium'], $class['size']['small']), 'and', false)
      ->orderByName()
      ->find();

    $this->assertEquals(0, count($disclosedAdultPics));
  }

  public function testNormalizeValues()
  {
    $this->assertEquals('foo', ClassifiedBehaviorTest1Peer::normalizeScopeName('foo'));
    $this->assertEquals('bar', ClassifiedBehaviorTest1Peer::normalizeClassificationName('bar'));
  }

  public function testPrepareClassifications()
  {
    $class = array(
      'audience' => array(
          'adult' => ClassificationQuery::create()->filterByScope('audience')->filterByClassification('adult')->findOne(),
          'kid' => ClassificationQuery::create()->filterByScope('audience')->filterByClassification('kid')->findOne(),),
      'size' => array(
          'small' => ClassificationQuery::create()->filterByScope('size')->filterByClassification('small')->findOne(),
          'medium' => ClassificationQuery::create()->filterByScope('size')->filterByClassification('medium')->findOne(),
          'big' => ClassificationQuery::create()->filterByScope('size')->filterByClassification('big')->findOne(),),
    );

    // $q->prepareClassifications(array('audience' => array('adult', 'kid'), 'size' => array('small')));
    $res = ClassifiedBehaviorTest1Peer::prepareClassifications(array('audience' => array('adult', 'kid'), 'size' => array('small')));
    $this->assertEquals(2, count($res));
    $this->assertTrue(isset($res['audience']));
    $this->assertTrue(isset($res['size']));
    $this->assertEquals('audience', $res['audience'][0]->getScope());
    $this->assertEquals('adult', $res['audience'][0]->getClassification());
    $this->assertEquals('audience', $res['audience'][1]->getScope());
    $this->assertEquals('kid', $res['audience'][1]->getClassification());
    $this->assertEquals('size', $res['size'][0]->getScope());
    $this->assertEquals('small', $res['size'][0]->getClassification());
    // $q->prepareClassifications('audience', array('adult', 'kid'));
    $res = ClassifiedBehaviorTest1Peer::prepareClassifications('audience', array('adult', 'kid'));
    $this->assertSame(array('audience' => array($class['audience']['adult'], $class['audience']['kid'])), $res);
    // $q->prepareClassifications('audience', 'kid');
    $res = ClassifiedBehaviorTest1Peer::prepareClassifications('audience', 'kid');
    $this->assertSame(array('audience' => array($class['audience']['kid'])), $res);
    // $q->prepareClassifications('size', array('small', 'medium'));
    $res = ClassifiedBehaviorTest1Peer::prepareClassifications('size', array('small', 'medium'));
    $this->assertSame(array('size' => array($class['size']['small'], $class['size']['medium'])), $res);
  }

  public function testFilterByClassificationNames()
  {
    $disclosedAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified('audience', 'adult', 'and', false)
      ->filterByClassified('size', 'medium', 'and', false)
      ->orderByName()
      ->find();

    $this->assertEquals(1, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());

    $disclosedAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified('audience', 'adult', 'and', false)
      ->filterByClassified('size', array('small', 'medium'), 'or', false)
      ->orderByName()
      ->find();

    $this->assertEquals(2, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());
    $this->assertEquals('Teddy', $disclosedAdultPics[1]->getName());

    $disclosedAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified('audience', 'adult', 'and', false)
      ->filterByClassified('size', array('small', 'medium'), 'and', false)
      ->orderByName()
      ->find();

    $this->assertEquals(0, count($disclosedAdultPics));
  }

  public function testFilterBySimpleArray()
  {
    $disclosedAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified(array('audience' =>  'adult'), 'and', false)
      ->filterByClassified(array('size' =>  'medium'), 'and', false)
      ->orderByName()
      ->find();

    $this->assertEquals(1, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());

    $licensed = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified(array('license' => array('MIT', 'creative common')), 'and', true)
      ->find();

    $this->assertEquals(1, count($licensed));
    $this->assertEquals('Teddy', $licensed[0]->getName());

    $disclosedAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified(array('audience' =>  'adult'), 'and', false)
      ->filterByClassified(array('size' => array('small',  'medium')), 'or', false)
      ->orderByName()
      ->find();

    $this->assertEquals(2, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());
    $this->assertEquals('Teddy', $disclosedAdultPics[1]->getName());

    $disclosedAdultPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified(array('audience' =>  'adult'), 'and', false)
      ->filterByClassified(array('size' => array('small',  'medium')), 'and', false)
      ->orderByName()
      ->find();

    $this->assertEquals(0, count($disclosedAdultPics));

    $adultOrSmallPics = ClassifiedBehaviorTest1Query::create()
      ->filterByClassified(array(
          'audience' =>  'adult',           // Only Hot pic match
          'license' => array('MIT', 'creative common')   // Only Teddy match
          ), 'AND', true)
      ->orderByName()
      ->find();

    $this->assertEquals(2, count($adultOrSmallPics));
  }
}
