<?php

namespace Sift\Practiceweb\Connectivity;

/**
 * Class HookLoader.
 *
 * @package Sift\Practiceweb\Connectivity
 */
class HookLoader
{
    protected $actions;
    protected $filters;

    /**
     * HookLoader constructor.
     */
    public function __construct()
    {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Add an action.
     *
     * @param string $hook
     *   Wordpress action name.
     * @param object|string $component
     *   Service class instance registering the action.
     * @param string $callback
     *   Service callback implementing the action.
     * @param int $priority
     *   Priority used by wordpress.
     * @param int $acceptedArgs
     *   Number of arguments callback expects.
     */
    public function addAction($hook, $component, $callback, $priority = 10, $acceptedArgs = 1)
    {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $acceptedArgs);
    }

    /**
     * Add a filter.
     *
     * @param string $hook
     *   Wordpress filter name.
     * @param object|string $component
     *   Service class instance registering the filter.
     * @param string $callback
     *   Service callback implementing the filter.
     * @param int $priority
     *   Priority used by wordpress.
     * @param int $acceptedArgs
     *   Number of arguments callback expects.
     */
    public function addFilter($hook, $component, $callback, $priority = 10, $acceptedArgs = 1)
    {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $acceptedArgs);
    }

    /**
     * Wrapper for adding actions and filters.
     *
     * @param array $hooks
     *   Array of hooks to update.
     * @param string $hook
     *   Hook name - action or filter.
     * @param object|string $component
     *   Service class instance registering the filter.
     * @param string $callback
     *   Service callback implementing the filter.
     * @param int $priority
     *   Priority used by wordpress.
     * @param int $acceptedArgs
     *   Number of arguments callback expects.
     *
     * @return array
     *   Updated hook list.
     */
    private function add(array $hooks, $hook, $component, $callback, $priority = 10, $acceptedArgs = 1)
    {
        $hooks[] = array(
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $acceptedArgs,
        );
        return $hooks;
    }

    /**
     * Processes the actions and filters to add them to wordpress.
     */
    public function run()
    {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'],$hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'],$hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
