<?php

require_once 'C:\wamp\www\framework\databaseAdapter.php';

/**
 * Test class for MySQLiDatabase.
 * Generated by PHPUnit on 2011-03-19 at 17:56:22.
 */
class MySQLiDatabaseTest extends PHPUnit_Framework_TestCase {

    /**
     * @var MySQLiDatabase
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = new MySQLiDatabase('localhost','root','','test');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        unset($this->object);
    }

	public function testSelectDatabase() {
		$this->assertTrue($this->object->selectDatabase('test'));
	}
    /**
		@dataProvider createProviderTrue
		@covers MySQLiDatabase::createTable
     */
    public function testCreateTableTrue($table,$params) {
        $this->assertTrue($this->object->createTable($table,$params));
    } 
	/**
		@dataProvider createProviderFalse
		@covers MySQLiDatabase::createTable
     */
    public function testCreateTableFalse($table,$params) {
        $this->assertFalse($this->object->createTable($table,$params));
    }
	
	public function createProviderTrue() {
		return 
			array(array('test1',array('id'=>array('INT','NOT NULL','AUTO_INCREMENT','PRIMARY KEY'))),
			array('test5',array('id'=>array('INT','NOT NULL','AUTO_INCREMENT','PRIMARY KEY'),'date'=>array('DATE','NOT NULL'))));
	}
	
	public function createProviderFalse() {
		return array(
			array('sdfasd\xcvadf',array('id'=>array('INT','NOT NULL','AUTO_INCREMENT','PRIMARY KEY'),'text'=>array('TEXT','NOT NULL'))),
			array('asfdafsvxcr>.tyeter',array('id'=>array('INT','NOT NULL','AUTO_INCREMENT','PRIMARY KEY'),'varchar'=>array('VARCHAR( 40 )','NOT NULL'))),
			array('!#$!324234',array('id'=>array('INT','NOT NULL','AUTO_INCREMENT','PRIMARY KEY'),'date'=>array('DATE','NOT NULL'))),
			);
			
	}
	/**
     * 
		@depends testCreateTableTrue
		@dataProvider createProviderTrue
     */
    public function testTableExists($table) {
       $this->assertTrue($this->object->tableExists($table));
    }

    /**
		@depends testTableExists
		@dataProvider alterProvider
     */
    public function testAlterTable($name,$param) {
        $this->assertTrue($this->object->alterTable($name,$param));
    }
	
	public function alterProvider() {
		return array(
			array('test5',array('name'=>'test2',
				'add'=>array('id2'=>array('INT','NOT NULL'),'text2'=>array('TEXT','NOT NULL')),
				'edit'=>array('id'=>array('id3'=>array('INT','NOT NULL'))),
				'delete'=>array('date')))
			
		);
	}

    /**
		@depends testTableExists
		@dataProvider createProviderTrue
     */
    public function testDropTable($name) {
		$this->assertTrue($this->object->dropTable($name));
    }


}

?>