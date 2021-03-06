<?php

use Mockery as m;

class EloquentModelTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
		EloquentModelStub::clearConnections();
	}


	public function testAttributeManipulation()
	{
		$model = new EloquentModelStub;
		$model->name = 'foo';
		$this->assertEquals('foo', $model->name);
		$this->assertTrue(isset($model->name));
		unset($model->name);
		$this->assertFalse(isset($model->name));

		// test mutation
		$model->list_items = array('name' => 'taylor');
		$this->assertEquals(array('name' => 'taylor'), $model->list_items);
		$attributes = $model->getAttributes();
		$this->assertEquals(json_encode(array('name' => 'taylor')), $attributes['list_items']);
	}


	public function testNewInstanceReturnsNewInstanceWithAttributesSet()
	{
		$model = new EloquentModelStub;
		$instance = $model->newInstance(array('name' => 'taylor'));
		$this->assertInstanceOf('EloquentModelStub', $instance);
		$this->assertEquals('taylor', $instance->name);
	}


	public function testCreateMethodSavesNewModel()
	{
		$_SERVER['__eloquent.saved'] = false;
		$model = EloquentModelSaveStub::create(array('name' => 'taylor'));
		$this->assertTrue($_SERVER['__eloquent.saved']);
		$this->assertEquals('taylor', $model->name);
	}


	public function testFindMethodCallsQueryBuilderCorrectly()
	{
		$result = EloquentModelFindStub::find(1);
		$this->assertEquals('foo', $result);
	}


	public function testWithMethodCallsQueryBuilderCorrectly()
	{
		$result = EloquentModelWithStub::with('foo', 'bar');
		$this->assertEquals('foo', $result);
	}


	public function testWithMethodCallsQueryBuilderCorrectlyWithArray()
	{
		$result = EloquentModelWithStub::with(array('foo', 'bar'));
		$this->assertEquals('foo', $result);
	}


	public function testUpdateProcess()
	{
		$model = $this->getMock('EloquentModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Illuminate\Database\Eloquent\Builder');
		$query->shouldReceive('where')->once()->with('id', '=', 1);
		$query->shouldReceive('update')->once()->with(array('id' => 1, 'name' => 'taylor'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');

		$model->id = 1;
		$model->name = 'taylor';
		$model->exists = true;
		$this->assertTrue($model->save());
	}


	public function testUpdateProcessWithoutTimestamps()
	{
		$model = $this->getMock('EloquentModelStub', array('newQuery', 'updateTimestamps'));
		$model->timestamps = false;
		$query = m::mock('Illuminate\Database\Eloquent\Builder');
		$query->shouldReceive('where')->once()->with('id', '=', 1);
		$query->shouldReceive('update')->once()->with(array('id' => 1, 'name' => 'taylor'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->never())->method('updateTimestamps');

		$model->id = 1;
		$model->name = 'taylor';
		$model->exists = true;
		$this->assertTrue($model->save());
	}


	public function testInsertProcess()
	{
		$model = $this->getMock('EloquentModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Illuminate\Database\Eloquent\Builder');
		$query->shouldReceive('insertGetId')->once()->with(array('name' => 'taylor'))->andReturn(1);
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');

		$model->name = 'taylor';
		$model->exists = false;
		$this->assertTrue($model->save());
		$this->assertEquals(1, $model->id);

		$model = $this->getMock('EloquentModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Illuminate\Database\Eloquent\Builder');
		$query->shouldReceive('insert')->once()->with(array('name' => 'taylor'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');
		$model->setIncrementing(false);

		$model->name = 'taylor';
		$model->exists = false;
		$this->assertTrue($model->save());
		$this->assertNull($model->id);
	}


	public function testDeleteProperlyDeletesModel()
	{
		$model = $this->getMock('Illuminate\Database\Eloquent\Model', array('newQuery', 'updateTimestamps'));
		$query = m::mock('stdClass');
		$query->shouldReceive('where')->once()->with('id', 1)->andReturn($query);
		$query->shouldReceive('delete')->once();
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->exists = true;
		$model->id = 1;
		$model->delete();
	}


	public function testNewQueryReturnsEloquentQueryBuilder()
	{
		$conn = m::mock('Illuminate\Database\Connection');
		$grammar = m::mock('Illuminate\Database\Query\Grammars\Grammar');
		$processor = m::mock('Illuminate\Database\Query\Processors\Processor');
		$conn->shouldReceive('getQueryGrammar')->once()->andReturn($grammar);
		$conn->shouldReceive('getPostProcessor')->once()->andReturn($processor);
		EloquentModelStub::addConnection('main', $conn);
		$model = new EloquentModelStub;
		$builder = $model->newQuery();
		$this->assertInstanceOf('Illuminate\Database\Eloquent\Builder', $builder);
		EloquentModelStub::clearConnections();
	}


	public function testGetAndSetTableOperations()
	{
		$model = new EloquentModelStub;
		$this->assertEquals('stub', $model->getTable());
		$model->setTable('foo');
		$this->assertEquals('foo', $model->getTable());
	}


	public function testGetKeyReturnsValueOfPrimaryKey()
	{
		$model = new EloquentModelStub;
		$model->id = 1;
		$this->assertEquals(1, $model->getKey());
		$this->assertEquals('id', $model->getKeyName());
	}


	public function testConnectionManagement()
	{
		$conn = m::mock('Illuminate\Database\Connection');
		EloquentModelStub::addConnection('main', $conn);
		$model = new EloquentModelStub;
		$this->assertTrue($model->getConnection() === $conn);
		$conn2 = m::mock('Illuminate\Database\Connection');
		EloquentModelStub::addConnection('sub', $conn2);
		$model = new EloquentModelStub;
		$model->setConnection('sub');
		$this->assertTrue($model->getConnection() === $conn2);
	}


	public function testToArray()
	{
		$model = new EloquentModelStub;
		$model->name = 'foo';
		$model->age = null;
		$model->password = 'password1';
		$model->setHidden(array('password'));
		$model->setRelation('names', new Illuminate\Database\Eloquent\Collection(array(
			new EloquentModelStub(array('bar' => 'baz')), new EloquentModelStub(array('bam' => 'boom'))
		)));
		$model->setRelation('partner', new EloquentModelStub(array('name' => 'abby')));
		$array = $model->toArray();

		$this->assertTrue(is_array($array));
		$this->assertEquals('foo', $array['name']);
		$this->assertEquals('baz', $array['names'][0]['bar']);
		$this->assertEquals('boom', $array['names'][1]['bam']);
		$this->assertEquals('abby', $array['partner']['name']);
		$this->assertFalse(isset($array['password']));
	}


	public function testFillable()
	{
		$model = new EloquentModelStub;
		$model->setFillable(array('name', 'age'));
		$model->fill(array('name' => 'foo', 'age' => 'bar', 'password' => 'baz'));
		$this->assertFalse(isset($model->password));
		$this->assertEquals('foo', $model->name);
		$this->assertEquals('bar', $model->age);
	}


	public function testHasOneCreatesProperRelation()
	{
		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasOne('EloquentModelSaveStub');
		$this->assertEquals('eloquent_model_stub_id', $relation->getForeignKey());
		EloquentModelStub::clearConnections();

		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasOne('EloquentModelSaveStub', 'foo');
		$this->assertEquals('foo', $relation->getForeignKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof EloquentModelSaveStub);
	}


	public function testMorphOneCreatesProperRelation()
	{
		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->morphOne('EloquentModelSaveStub', 'morph');
		$this->assertEquals('morph_id', $relation->getForeignKey());
		$this->assertEquals('morph_type', $relation->getMorphType());
		$this->assertEquals('EloquentModelStub', $relation->getMorphClass());
		EloquentModelStub::clearConnections();
	}


	public function testHasManyCreatesProperRelation()
	{
		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasMany('EloquentModelSaveStub');
		$this->assertEquals('eloquent_model_stub_id', $relation->getForeignKey());
		EloquentModelStub::clearConnections();

		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasMany('EloquentModelSaveStub', 'foo');
		$this->assertEquals('foo', $relation->getForeignKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof EloquentModelSaveStub);
	}


	public function testMorphManyCreatesProperRelation()
	{
		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->morphMany('EloquentModelSaveStub', 'morph');
		$this->assertEquals('morph_id', $relation->getForeignKey());
		$this->assertEquals('morph_type', $relation->getMorphType());
		$this->assertEquals('EloquentModelStub', $relation->getMorphClass());
		EloquentModelStub::clearConnections();
	}


	public function testBelongsToCreatesProperRelation()
	{
		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToStub();
		$this->assertEquals('belongs_to_stub_id', $relation->getForeignKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof EloquentModelSaveStub);
		EloquentModelStub::clearConnections();

		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToExplicitKeyStub();
		$this->assertEquals('foo', $relation->getForeignKey());
	}


	public function testMorphToCreatesProperRelation()
	{
		$model = m::mock('Illuminate\Database\Eloquent\Model[belongsTo]');
		$model->foo_type = 'FooClass';
		$model->shouldReceive('belongsTo')->with('FooClass', 'foo_id');
		$relation = $model->morphTo('foo');

		$model = m::mock('EloquentModelStub[belongsTo]');
		$model->morph_to_stub_type = 'FooClass';
		$model->shouldReceive('belongsTo')->with('FooClass', 'morph_to_stub_id');
		$relation = $model->morphToStub();
	}


	public function testBelongsToManyCreatesProperRelation()
	{
		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToMany('EloquentModelSaveStub');
		$this->assertEquals('eloquent_model_save_stub_eloquent_model_stub.eloquent_model_stub_id', $relation->getForeignKey());
		$this->assertEquals('eloquent_model_save_stub_eloquent_model_stub.eloquent_model_save_stub_id', $relation->getOtherKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof EloquentModelSaveStub);
		EloquentModelStub::clearConnections();

		$model = new EloquentModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToMany('EloquentModelSaveStub', 'table', 'foreign', 'other');
		$this->assertEquals('table.foreign', $relation->getForeignKey());
		$this->assertEquals('table.other', $relation->getOtherKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof EloquentModelSaveStub);
	}


	protected function addMockConnection($model)
	{
		$model->addConnection('main', m::mock('Illuminate\Database\Connection'));
		$model->getConnection()->shouldReceive('getQueryGrammar')->andReturn(m::mock('Illuminate\Database\Query\Grammars\Grammar'));
		$model->getConnection()->shouldReceive('getPostProcessor')->andReturn(m::mock('Illuminate\Database\Query\Processors\Processor'));
	}

}

class EloquentModelStub extends Illuminate\Database\Eloquent\Model {
	protected $table = 'stub';
	public function getListItems($value)
	{
		return json_decode($value, true);
	}
	public function setListItems($value)
	{
		return json_encode($value);
	}
	public function belongsToStub()
	{
		return $this->belongsTo('EloquentModelSaveStub');
	}
	public function morphToStub()
	{
		return $this->morphTo();
	}
	public function belongsToExplicitKeyStub()
	{
		return $this->belongsTo('EloquentModelSaveStub', 'foo');
	}
}

class EloquentModelSaveStub extends Illuminate\Database\Eloquent\Model {
	protected $table = 'save_stub';
	public function save() { $_SERVER['__eloquent.saved'] = true; }
	public function setIncrementing($value)
	{
		$this->incrementing = $value;
	}
}

class EloquentModelFindStub extends Illuminate\Database\Eloquent\Model {
	public function newQuery()
	{
		$mock = m::mock('Illuminate\Database\Eloquent\Builder');
		$mock->shouldReceive('find')->once()->with(1, array('*'))->andReturn('foo');
		return $mock;
	}
}

class EloquentModelWithStub extends Illuminate\Database\Eloquent\Model {
	public function newQuery()
	{
		$mock = m::mock('Illuminate\Database\Eloquent\Builder');
		$mock->shouldReceive('with')->once()->with(array('foo', 'bar'))->andReturn('foo');
		return $mock;
	}
}