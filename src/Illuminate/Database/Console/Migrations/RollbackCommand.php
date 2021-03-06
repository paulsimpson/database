<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;

class RollbackCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'migrate:rollback';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Rollback the last database migration';

	/**
	 * The migrator instance.
	 *
	 * @var Illuminate\Database\Migrations\Migrator
	 */
	protected $migrator;

	/**
	 * Create a new migration rollback command instance.
	 *
	 * @param  Illuminate\Database\Migrations\Migrator  $migrator
	 * @return void
	 */
	public function __construct(Migrator $migrator)
	{
		parent::__construct();

		$this->migrator = $migrator;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$pretend = $this->input->getOption('pretend');

		$this->migrator->rollbackMigrations($this->output, $pretend);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run'),
		);
	}

}