<?php namespace Illuminate\Database\Migrations;

use Illuminate\Filesystem;

class MigrationCreator {

	/**
	 * The filesystem instance.
	 *
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * Create a new migration creator instance.
	 *
	 * @param  Illuminate\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files)
	{
		$this->files = $files;
	}

	/**
	 * Create a new migration at the given path.
	 *
	 * @param  string  $name
	 * @param  string  $path
	 * @param  string  $table
	 * @param  bool    $create
	 * @return void
	 */
	public function create($name, $path, $table = null, $create = false)
	{
		$path = $this->getPath($name, $path);

		$stub = $this->getStub($table, $create);

		$this->files->put($path, $this->populateStub($name, $stub, $table));
	}

	/**
	 * Get the migration stub file.
	 *
	 * @param  string  $table
	 * @return void
	 */
	protected function getStub($table, $create)
	{
		if (is_null($table))
		{
			return $this->files->get($this->getStubPath().'/blank.php');
		}

		// We also have stubs for creating new tables and modifying existing tables
		// to save the developer some typing when they are creating a new tables
		// or modifying existing tables. We'll grab the appropriate stub here.
		else
		{
			$stub = $create ? 'create.php' : 'update.php';

			return $this->files->get($this->getStubPath()."/{$stub}");
		}
	}

	/**
	 * Populate the place-holders in the migration stub.
	 *
	 * @param  string  $name
	 * @param  string  $stub
	 * @param  string  $table
	 * @return string
	 */
	protected function populateStub($name, $stub, $table)
	{
		$stub = str_replace('{{class}}', camel_case($name), $stub);

		// Here we will replace the table place-holders with the table specified by
		// the developer. This is useful for quickly creaeting a tables creation
		// or update migration from the console instead of typing it manually
		if ( ! is_null($table))
		{
			$stub = str_replace('{{table}}', $table, $stub);
		}

		return $stub;
	}

	/**
	 * Get the full path name to the migration.
	 *
	 * @param  string  $name
	 * @param  string  $path
	 * @return string
	 */
	protected function getPath($name, $path)
	{
		return $path.'/'.$this->getDatePrefix().'_'.$name.'.php';
	}

	/**
	 * Get the date prefix for the migration.
	 *
	 * @return int
	 */
	protected function getDatePrefix()
	{
		return date('Y_m_d_His');
	}

	/**
	 * Get the path to the stubs.
	 *
	 * @return string
	 */
	public function getStubPath()
	{
		return __DIR__.'/stubs';
	}

	/**
	 * Get the filesystem instance.
	 *
	 * @return Illuminate\Filesystem
	 */
	public function getFilesystem()
	{
		return $this->files;
	}

}