<?php

use Mockery as m;
use Illuminate\Database\Migrations\MigrationCreator;

class MigrationCreatorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicCreateMethodStoresMigrationFile()
	{
		$creator = $this->getCreator();
		$creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
		$creator->getFilesystem()->shouldReceive('get')->once()->with($creator->getStubPath().'/blank.php')->andReturn('{{class}}');
		$creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar');

		$creator->create('create_bar', 'foo');
	}


	public function testTableUpdateMigrationStoresMigrationFile()
	{
		$creator = $this->getCreator();
		$creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
		$creator->getFilesystem()->shouldReceive('get')->once()->with($creator->getStubPath().'/update.php')->andReturn('{{class}} {{table}}');
		$creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar baz');

		$creator->create('create_bar', 'foo', 'baz');
	}


	public function testTableCreationMigrationStoresMigrationFile()
	{
		$creator = $this->getCreator();
		$creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
		$creator->getFilesystem()->shouldReceive('get')->once()->with($creator->getStubPath().'/create.php')->andReturn('{{class}} {{table}}');
		$creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar baz');

		$creator->create('create_bar', 'foo', 'baz', true);
	}


	protected function getCreator()
	{
		$files = m::mock('Illuminate\Filesystem');

		return $this->getMock('Illuminate\Database\Migrations\MigrationCreator', array('getDatePrefix'), array($files));
	}

}