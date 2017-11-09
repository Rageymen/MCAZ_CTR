<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use ArrayIterator;
use Cake\Console\CommandScanner;
use Cake\Console\Shell;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * Collection for Commands.
 *
 * Used by Applications to whitelist their console commands.
 * CakePHP will use the mapped commands to construct and dispatch
 * shell commands.
 */
class CommandCollection implements IteratorAggregate, Countable
{
    /**
     * Command list
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Constructor
     *
     * @param array $commands The map of commands to add to the collection.
     */
    public function __construct(array $commands = [])
    {
        foreach ($commands as $name => $command) {
            $this->add($name, $command);
        }
    }

    /**
     * Add a command to the collection
     *
     * @param string $name The name of the command you want to map.
     * @param string|\Cake\Console\Shell $command The command to map.
     * @return $this
     */
    public function add($name, $command)
    {
        // Once we have a new Command class this should check
        // against that interface.
        if (!is_subclass_of($command, Shell::class)) {
            $class = is_string($command) ? $command : get_class($command);
            throw new InvalidArgumentException(
                "Cannot use '$class' for command '$name' it is not a subclass of Cake\Console\Shell."
            );
        }

        $this->commands[$name] = $command;

        return $this;
    }

    /**
     * Add multiple commands at once.
     *
     * @param array $commands A map of command names => command classes/instances.
     * @return $this
     * @see \Cake\Console\CommandCollection::add()
     */
    public function addMany(array $commands)
    {
        foreach ($commands as $name => $class) {
            $this->add($name, $class);
        }

        return $this;
    }

    /**
     * Remove a command from the collection if it exists.
     *
     * @param string $name The named shell.
     * @return $this
     */
    public function remove($name)
    {
        unset($this->commands[$name]);

        return $this;
    }

    /**
     * Check whether the named shell exists in the collection.
     *
     * @param string $name The named shell.
     * @return bool
     */
    public function has($name)
    {
        return isset($this->commands[$name]);
    }

    /**
     * Get the target for a command.
     *
     * @param string $name The named shell.
     * @return string|\Cake\Console\Shell Either the shell class or an instance.
     * @throws \InvalidArgumentException when unknown commands are fetched.
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException("The $name is not a known command name.");
        }

        return $this->commands[$name];
    }

    /**
     * Implementation of IteratorAggregate.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->commands);
    }

    /**
     * Implementation of Countable.
     *
     * Get the number of commands in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->commands);
    }

    /**
     * Automatically discover shell commands in CakePHP, the application and all plugins.
     *
     * Commands will be located using filesystem conventions. Commands are
     * discovered in the following order:
     *
     * - CakePHP provided commands
     * - Application commands
     * - Plugin commands
     *
     * Commands from plugins will be added based on the order plugins are loaded.
     * Plugin shells will attempt to use a short name. If however, a plugin
     * provides a shell that conflicts with CakePHP or the application shells,
     * the full `plugin_name.shell` name will be used. Plugin shells are added
     * in the order that plugins were loaded.
     *
     * @return array An array of command names and their classes.
     */
    public function autoDiscover()
    {
        $scanner = new CommandScanner();
        $shells = $scanner->scanAll();

        $adder = function ($out, $shells, $key) {
            if (empty($shells[$key])) {
                return $out;
            }

            foreach ($shells[$key] as $info) {
                $name = $info['name'];
                $addLong = $name !== $info['fullName'];

                // If the short name has been used, use the full name.
                // This allows app shells to have name preference.
                // and app shells to overwrite core shells.
                if (isset($out[$name]) && $addLong) {
                    $name = $info['fullName'];
                }

                $out[$name] = $info['class'];
                if ($addLong) {
                    $out[$info['fullName']] = $info['class'];
                }
            }

            return $out;
        };

        $out = $adder([], $shells, 'CORE');
        $out = $adder($out, $shells, 'app');
        foreach (array_keys($shells['plugins']) as $key) {
            $out = $adder($out, $shells['plugins'], $key);
        }

        return $out;
    }
}
