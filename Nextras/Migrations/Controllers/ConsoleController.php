<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Controllers;

use Nextras\Migrations\Engine;
use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\IExtensionHandler;
use Nextras\Migrations\Printers;


class ConsoleController
{
	/** @var Engine\Runner */
	private $runner;

	/** @var string */
	private $mode;

	/** @var array (name => Group) */
	private $groups;


	public function __construct(IDriver $driver)
	{
		$printer = new Printers\Console;
		$this->runner = new Engine\Runner($driver, $printer);
		$this->mode = Engine\Runner::MODE_CONTINUE;
	}


	public function addGroup($name, $dir, array $dependencies = array())
	{
		$group = new Group;
		$group->name = $name;
		$group->directory = $dir;
		$group->dependencies = $dependencies;
		$group->enabled = FALSE;

		$this->groups[$name] = $group;
		return $this;
	}


	public function addExtension($extension, IExtensionHandler $handler)
	{
		$this->runner->addExtensionHandler($extension, $handler);
		return $this;
	}


	public function run()
	{
		$this->printHeader();
		$this->processArguments();
		$this->registerGroups();
		$this->runner->run($this->mode);
	}


	private function printHeader()
	{
		printf("Migrations\n");
		printf("------------------------------------------------------------\n");
	}


	private function processArguments()
	{
		$arguments = array_slice($_SERVER['argv'], 1);
		$help = count($arguments) === 0;
		$groups = FALSE;
		$error = FALSE;

		foreach ($arguments as $argument) {
			if (strncmp($argument, '--', 2) === 0) {
				if ($argument === '--reset') {
					$this->mode = Engine\Runner::MODE_RESET;
				} elseif ($argument === '--help') {
					$help = TRUE;
				} else {
					fprintf(STDERR, "Error: Unknown option '%s'\n", $argument);
					$error = TRUE;
				}

			} else {
				if (isset($this->groups[$argument])) {
					$this->groups[$argument]->enabled = TRUE;
					$groups = TRUE;
				} else {
					fprintf(STDERR, "Error: Unknown group '%s'\n", $argument);
					$error = TRUE;
				}
			}
		}

		if (!$groups && !$help) {
			fprintf(STDERR, "Error: At least one group must be enabled.\n");
			$error = TRUE;
		}

		if ($error) {
			printf("\n");
		}

		if ($help || $error) {
			printf("Usage: %s group1 [, group2, ...] [--reset] [--help]\n", basename($_SERVER['argv'][0]));
			printf("Registered groups:\n");
			foreach (array_keys($this->groups) as $group) {
				printf("  %s\n", $group);
			}
			printf("\nSwitches:\n");
			printf("  --reset      drop all tables and views in database and start from scratch\n");
			printf("  --help       show this help\n");
			exit(intval($error));
		}
	}


	private function registerGroups()
	{
		foreach ($this->groups as $group) {
			$this->runner->addGroup($group);
		}
	}

}
