<?php
/*
 *	$Id: VersionableBehaviorTest.php 1460 2010-01-17 22:36:48Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */


/**
 * Test for ClassifiableBehavior
 *
 * @author     Julien Muetton
 * @version    $Revision$
 * @package    generator.behavior.classifiable
 */
class ClassifiableBehaviorTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
  	if (!class_exists('ClassifiableBehaviorTest2')) {
      $schema = <<<EOF
<database name="classifiable_behavior_test_overriden_properties">
  <table name="classification_2">
    <column name="id" type="INTEGER" primaryKey="true" autoincrement="true" />
    <column name="user_group" type="VARCHAR" size="255" />
    <column name="value" type="VARCHAR" size="255" />
  </table>
  <table name="classifiable_behavior_test_2">
    <column name="id" type="INTEGER" primaryKey="true" autoincrement="true" />
    <column name="name" type="VARCHAR" size="255" />
    <behavior name="classifiable">
      <parameter name="classification_table" value="classification_2" />
      <parameter name="classification_column" value="value" />
      <parameter name="scope_column" value="user_group" />
    </behavior>
  </table>
</database>
EOF;
			PropelQuickBuilder::buildSchema($schema);
      $class = array();
      $classifications = array(
        'size'    => array('big', 'small', 'medium'),
        'license' => array('MIT', 'GPL', 'BSD', 'commercial', 'creative common'),
        'format'  => array('jpg', 'png', 'bmp'),
        'intended audience'  => array('kid', 'adult'),
      );

      foreach($classifications as $scope => $values) {
        foreach ($values as $value) {
          $c = new Classification2();
          $c->setUserGroup($scope);
          $c->setValue($value);
          $c->save();
          $class[$scope][$value] = $c;
        }
      }

      $teddy = new ClassifiableBehaviorTest2();
      $teddy->setName('Teddy');
      $teddy->addClassification2($class['size']['small']);
      $teddy->addClassification2($class['license']['creative common']);
      $teddy->addClassification2($class['license']['MIT']);
      $teddy->addClassification2($class['format']['jpg']);
      $teddy->save();

      $hotPic = new ClassifiableBehaviorTest2();
      $hotPic->setName('Hot pic !');
      $hotPic->addClassification2($class['size']['medium']);
      $hotPic->addClassification2($class['license']['commercial']);
      $hotPic->addClassification2($class['intended audience']['adult']);
      $hotPic->save();
    }
  	if (!class_exists('ClassifiableBehaviorTest1')) {
      $schema = <<<EOF
<database name="classifiable_behavior_test_applied_on_table">
  <table name="classifiable_behavior_test_1">
    <column name="id" type="INTEGER" primaryKey="true" autoincrement="true" />
    <column name="name" type="VARCHAR" size="255" />
    <behavior name="classifiable" />
  </table>
</database>
EOF;
			PropelQuickBuilder::buildSchema($schema);
      $class = array();
      $classifications = array(
        'size'    => array('big', 'small', 'medium'),
        'license' => array('MIT', 'GPL', 'BSD', 'commercial', 'creative common'),
        'format'  => array('jpg', 'png', 'bmp'),
        'intended audience'  => array('kid', 'adult'),
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

      $teddy = new ClassifiableBehaviorTest1();
      $teddy->setName('Teddy');
      $teddy->addClassification($class['size']['small']);
      $teddy->addClassification($class['license']['creative common']);
      $teddy->addClassification($class['license']['MIT']);
      $teddy->addClassification($class['format']['jpg']);
      $teddy->save();

      $hotPic = new ClassifiableBehaviorTest1();
      $hotPic->setName('Hot pic !');
      $hotPic->addClassification($class['size']['medium']);
      $hotPic->addClassification($class['license']['commercial']);
      $hotPic->addClassification($class['intended audience']['adult']);
      $hotPic->save();
    }
  }

  public function getClassificationObjects()
  {
    return array(
      'intended audience' => array(
          'kid' => ClassificationQuery::create()->filterByScope('intended audience')->filterByClassification('kid')->findOne(),
          'adult' => ClassificationQuery::create()->filterByScope('intended audience')->filterByClassification('adult')->findOne(),),
      'size' => array(
          'small' => ClassificationQuery::create()->filterByScope('size')->filterByClassification('small')->findOne(),
          'medium' => ClassificationQuery::create()->filterByScope('size')->filterByClassification('medium')->findOne(),
          'big' => ClassificationQuery::create()->filterByScope('size')->filterByClassification('big')->findOne(),),
        );
  }

  public function testActiveQueryMethodsExtists()
  {
    $this->assertTrue(method_exists('ClassifiableBehaviorTest1Query', 'filterByClassified'));
    $this->assertTrue(method_exists('ClassifiableBehaviorTest1Query', 'conditionForDisclosed'));
    $this->assertTrue(method_exists('ClassifiableBehaviorTest1Query', 'conditionForClassifiable'));
  }

  public function testActiveRecordMethodsExtists()
  {
    $this->assertTrue(method_exists('ClassifiableBehaviorTest1', 'classify'));
    $this->assertTrue(method_exists('ClassifiableBehaviorTest1', 'isClassified'));
    $this->assertTrue(method_exists('ClassifiableBehaviorTest1', 'getClassification'));
    $this->assertTrue(method_exists('ClassifiableBehaviorTest1', 'disclose'));
  }

  public function testClassificationParanoidQuery()
  {
    $class = $this->getClassificationObjects();
    $paranoidAdultPics = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified('intended audience', $class['intended audience']['adult'], 'and', true)
      ->orderByName()
      ->find();

    $this->assertEquals(1, count($paranoidAdultPics));
    $this->assertEquals('Hot pic !', $paranoidAdultPics[0]->getName());

    $licensed = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified('license', array('MIT', 'creative common'), 'and', true)
      ->find();

    $this->assertEquals(1, count($licensed));
    $this->assertEquals('Teddy', $licensed[0]->getName());
  }

  public function testClassificationDisclosedQuery()
  {
    $class = $this->getClassificationObjects();

    $disclosedAdultPics = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified('intended audience', $class['intended audience']['adult'], 'and', false)
      ->orderByName()
      ->find();

    $this->assertEquals(2, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());
    $this->assertEquals('Teddy', $disclosedAdultPics[1]->getName());
  }

  public function testFilterByDisclosed()
  {
    $disclosedPublicPics = ClassifiableBehaviorTest1Query::create()
      ->filterByDisclosed('intended audience')
      ->orderByName()
      ->find();

    $this->assertEquals(1, count($disclosedPublicPics));
    $this->assertEquals('Teddy', $disclosedPublicPics[0]->getName());
  }

  public function testFilterOnSeveralClassifications()
  {
    $class = $this->getClassificationObjects();

    $disclosedAdultPics = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified('intended audience', $class['intended audience']['adult'], 'and', false)
      ->filterByClassified('size', $class['size']['medium'], 'and', false)
      ->orderByName()
      ->find();

    $this->assertEquals(1, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());

    $disclosedAdultPics = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified('intended audience', $class['intended audience']['adult'], 'and', false)
      ->filterByClassified('size', array($class['size']['medium'], $class['size']['small']), 'or', false)
      ->orderByName()
      ->find();

    $this->assertEquals(2, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());
    $this->assertEquals('Teddy', $disclosedAdultPics[1]->getName());

    $disclosedAdultPics = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified('intended audience', $class['intended audience']['adult'], 'and', false)
      ->filterByClassified('size', array($class['size']['medium'], $class['size']['small']), 'and', false)
      ->orderByName()
      ->find();

    $this->assertEquals(0, count($disclosedAdultPics));
  }

  public function testNormalizeValues()
  {
    $this->assertEquals('foo', ClassifiableBehaviorTest1Peer::normalizeScopeName('foo'));
    $this->assertEquals('bar', ClassifiableBehaviorTest1Peer::normalizeClassificationName('bar'));
  }

  public function testPrepareClassifications()
  {
    $class = $this->getClassificationObjects();

    // $q->prepareClassifications(array('intended audience' => array('adult', 'kid'), 'size' => array('small')));
    $res = ClassifiableBehaviorTest1Peer::prepareClassifications(array('intended audience' => array('adult', 'kid'), 'size' => array('small')));
    $this->assertEquals(2, count($res));
    $this->assertTrue(isset($res['intended audience']));
    $this->assertTrue(isset($res['size']));
    $this->assertEquals('intended audience', $res['intended audience']['adult']->getScope());
    $this->assertEquals('adult', $res['intended audience']['adult']->getClassification());
    $this->assertEquals('intended audience', $res['intended audience']['kid']->getScope());
    $this->assertEquals('kid', $res['intended audience']['kid']->getClassification());
    $this->assertEquals('size', $res['size']['small']->getScope());
    $this->assertEquals('small', $res['size']['small']->getClassification());
    // $q->prepareClassifications('intended audience', array('adult', 'kid'));
    $res = ClassifiableBehaviorTest1Peer::prepareClassifications('intended audience', array('adult', 'kid'));
    $this->assertSame(array('intended audience' => array(
                                'adult' => $class['intended audience']['adult'],
                                'kid' => $class['intended audience']['kid'])), $res);
    // $q->prepareClassifications('intended audience', 'kid');
    $res = ClassifiableBehaviorTest1Peer::prepareClassifications('intended audience', 'kid');
    $this->assertSame(array('intended audience' => array('kid' => $class['intended audience']['kid'])), $res);
    // $q->prepareClassifications('size', array('small', 'medium'));
    $res = ClassifiableBehaviorTest1Peer::prepareClassifications('size', array('small', 'medium'));
    $this->assertSame(array('size' => array('small' => $class['size']['small'], 'medium' => $class['size']['medium'])), $res);
  }

  public function testFilterByClassificationNames()
  {
    $disclosedAdultPics = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified('intended audience', 'adult', 'and', false)
      ->filterByClassified('size', 'medium', 'and', false)
      ->orderByName()
      ->find();

    $this->assertEquals(1, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());

    $disclosedAdultPics = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified('intended audience', 'adult', 'and', false)
      ->filterByClassified('size', array('small', 'medium'), 'or', false)
      ->orderByName()
      ->find();

    $this->assertEquals(2, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());
    $this->assertEquals('Teddy', $disclosedAdultPics[1]->getName());

    $disclosedAdultPics = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified('intended audience', 'adult', 'and', false)
      ->filterByClassified('size', array('small', 'medium'), 'and', false)
      ->orderByName()
      ->find();

    $this->assertEquals(0, count($disclosedAdultPics));
  }

  public function testFilterBySimpleArray()
  {
    $disclosedAdultPics = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified(array('intended audience' =>  'adult'), 'and', false)
      ->filterByClassified(array('size' =>  'medium'), 'and', false)
      ->orderByName()
      ->find();

    $this->assertEquals(1, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());

    $licensed = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified(array('license' => array('MIT', 'creative common')), 'and', true)
      ->find();

    $this->assertEquals(1, count($licensed));
    $this->assertEquals('Teddy', $licensed[0]->getName());

    $disclosedAdultPics = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified(array('intended audience' =>  'adult'), 'and', false)
      ->filterByClassified(array('size' => array('small',  'medium')), 'or', false)
      ->orderByName()
      ->find();

    $this->assertEquals(2, count($disclosedAdultPics));
    $this->assertEquals('Hot pic !', $disclosedAdultPics[0]->getName());
    $this->assertEquals('Teddy', $disclosedAdultPics[1]->getName());

    $disclosedAdultPics = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified(array('intended audience' =>  'adult'), 'and', false)
      ->filterByClassified(array('size' => array('small',  'medium')), 'and', false)
      ->orderByName()
      ->find();

    $this->assertEquals(0, count($disclosedAdultPics));

    $adultOrSmallPics = ClassifiableBehaviorTest1Query::create()
      ->filterByClassified(array(
          'intended audience' =>  'adult',           // Only Hot pic match
          'license' => array('MIT', 'creative common')   // Only Teddy match
          ), 'AND', true)
      ->orderByName()
      ->find();

    $this->assertEquals(2, count($adultOrSmallPics));
  }

  public function testActiveRecoordClassification()
  {
    $pic = new ClassifiableBehaviorTest1();
    $this->assertTrue($pic->isDisclosed());
    $this->assertEquals(array(), $pic->getClassification());

    $pic->classify(array(
        'intended audience'  => array('adult', 'kid'),
        'size'      => 'big'));
    $this->assertEquals(2, count($pic->getClassification()));
    $this->assertTrue($pic->isDisclosed('license'));
    $this->assertFalse($pic->isDisclosed('size'));
    $this->assertFalse($pic->isDisclosed('intended audience'));

    $this->assertTrue($pic->isClassified('size', 'big'));
    $this->assertTrue($pic->isClassified('intended audience', 'adult'));
    $this->assertTrue($pic->isClassified('intended audience', 'kid'));
    $this->assertFalse($pic->isClassified('size', 'small'));
    $this->assertTrue($pic->isClassified(array(
                      'intended audience'  => array('kid', 'adult'),
                      'size'      => array('big', 'small')), 'or'));
    $this->assertFalse($pic->isClassified(array(
                      'intended audience'  => array('kid', 'adult'),
                      'size'      => array('big', 'small')), 'and'));
    $this->assertFalse($pic->isClassified(array(
                      'intended audience'  => array('kid', 'adult'),
                      'size'      => array('big', 'small')), 'xor'));
    $this->assertTrue($pic->isClassified(array(
                      'intended audience'  => array('kid'),
                      'size'      => array('big', 'small')), 'xor'));
    $this->assertTrue($pic->isClassified(array(
                      'intended audience'  => array('kid', 'adult'),
                      'size'      => 'big'), 'and'));
    $this->assertFalse($pic->isClassified(array(
                      'license' => 'MIT'), 'and', true));
    $this->assertTrue($pic->isClassified(array(
                      'license' => 'MIT'), 'and', false));

    // check overrides
    $pic->classify(array(
        'size'      => 'small',
        'license'   => 'MIT'));
    $this->assertTrue($pic->isClassified('size', 'small'));
    $this->assertTrue($pic->isClassified('size', 'big'));
    $this->assertTrue($pic->isClassified('license', 'MIT'));
  }

  public function testDisclose()
  {
    $class = $this->getClassificationObjects();
    $pic = new ClassifiableBehaviorTest1();
    $pic->setName('disclose test');
    $pic->classify(array(
      'intended audience'  => array($class['intended audience']['adult'], $class['intended audience']['kid']),
      'size'      => array($class['size']['small'])));

    $this->assertEquals(array(
      'intended audience'  => array('adult' => $class['intended audience']['adult'], 'kid' => $class['intended audience']['kid']),
      'size'      => array('small' => $class['size']['small'])), $pic->getClassification());

    $pic->disclose();
    $this->assertTrue($pic->isDisclosed());

    $pic->classify(array(
      'intended audience'  => array($class['intended audience']['adult'], $class['intended audience']['kid']),
      'size'      => array($class['size']['small'])));

    $pic->disclose('intended audience');
    $this->assertEquals(array(
      'size'      => array('small' => $class['size']['small'])), $pic->getClassification());

    $pic->classify(array(
      'intended audience'  => array($class['intended audience']['adult'], $class['intended audience']['kid']),
      'size'      => array($class['size']['small'])));

    $pic->disclose('intended audience', 'kid');
    $this->assertEquals(array(
      'intended audience'  => array('adult' => $class['intended audience']['adult']),
      'size'      => array('small' => $class['size']['small'])), $pic->getClassification());

    $pic->disclose('intended audience');
    $this->assertEquals(array(
      'size'      => array('small' => $class['size']['small'])), $pic->getClassification());

    $pic->disclose();
    $this->assertTrue($pic->isDisclosed());
  }

  public function testDiscloseLoop()
  {
    $class = $this->getClassificationObjects();
    $pic = new ClassifiableBehaviorTest1();
    $pic->setName('disclose test');

    $pic->disclose('intended audience');
    $pic->classify('intended audience', 'adult');
    $this->assertEquals(array(
      'intended audience'  => array('adult' => $class['intended audience']['adult']),), $pic->getClassification());

    $pic->disclose('intended audience');
    $pic->classify('intended audience', 'kid');
    $this->assertEquals(array(
      'intended audience'  => array('kid' => $class['intended audience']['kid']),), $pic->getClassification());

    $pic->disclose('size');
    $pic->classify('size', 'small');
    $this->assertEquals(array(
      'intended audience'  => array('kid' => $class['intended audience']['kid']),
      'size'      => array('small' => $class['size']['small'])), $pic->getClassification());

  }

/* now with specified parameters */

  public function testOverridePropertiesQueryMethodsExists()
  {
    $this->assertTrue(method_exists('ClassifiableBehaviorTest2Query', 'filterByClassified'));
    $this->assertTrue(method_exists('ClassifiableBehaviorTest2Query', 'conditionForDisclosed'));
    $this->assertTrue(method_exists('ClassifiableBehaviorTest2Query', 'conditionForClassifiable'));
  }

  public function testOverridePropertiesActiveRecordMethodsExtists()
  {
    $this->assertTrue(method_exists('ClassifiableBehaviorTest2', 'classify'));
    $this->assertTrue(method_exists('ClassifiableBehaviorTest2', 'isClassified'));
    $this->assertTrue(method_exists('ClassifiableBehaviorTest2', 'getClassification'));
    $this->assertTrue(method_exists('ClassifiableBehaviorTest2', 'disclose'));
  }

  public function testOverridePropertiesActiveRecoordClassification()
  {
    $pic = new ClassifiableBehaviorTest2();
    $this->assertTrue($pic->isDisclosed());
    $this->assertEquals(array(), $pic->getClassification());

    $pic->classify(array(
        'intended audience'  => array('adult', 'kid'),
        'size'      => 'big'));
    $this->assertEquals(2, count($pic->getClassification()));
    $this->assertTrue($pic->isDisclosed('license'));
    $this->assertFalse($pic->isDisclosed('size'));
    $this->assertFalse($pic->isDisclosed('intended audience'));

    $this->assertTrue($pic->isClassified('size', 'big'));
    $this->assertTrue($pic->isClassified('intended audience', 'adult'));
    $this->assertTrue($pic->isClassified('intended audience', 'kid'));

    $this->assertTrue($pic->isClassified(array(
                      'intended audience'  => array('kid', 'adult'),
                      'size'      => 'big'), 'and'));
  }
}
