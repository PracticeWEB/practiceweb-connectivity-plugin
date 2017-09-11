<?php

namespace Sift\Practiceweb\Connectivity;

/**
 * Class ServiceAbstract.
 *
 * @package Sift\Practiceweb\Connectivity
 */
abstract class ServiceAbstract
{

    /**
     * Hook loader.
     *
     * @var HookLoader
     */
    protected $hookLoader;

    /**
     * Template handler.
     *
     * @var TemplateHandler
     */
    protected $templateHandler;

    /**
     * Service constructor.
     *
     * @param HookLoader $loader
     *   Hook loader object.
     * @param TemplateHandler $handler
     *   Template handler object.
     */
    public function __construct(HookLoader $loader, TemplateHandler $handler)
    {
        $this->hookLoader = $loader;
        $this->templateHandler = $handler;
        $this->addFilters();
        $this->addActions();
    }

    /**
     * Add a filter defined in the service.
     *
     * @param string $hook
     *   Filter hook name.
     * @param string $callback
     *   Callback method name.
     * @param int $priority
     *   Priority to set. Defaults to 10 as per wordpress' add_action().
     * @param int $acceptedArgs
     *   Number of args to handle. Defaults to 1 as per wordpress' add_action().
     */
    public function addFilter($hook, $callback, $priority = 10, $acceptedArgs = 1)
    {
        $this->hookLoader->addFilter($hook, $this, $callback, $priority, $acceptedArgs);
    }

    /**
     * Add an action defined in the service.
     *
     * @param string $hook
     *   Action hook name.
     * @param string $callback
     *   Callback method name.
     * @param int $priority
     *   Priority to set. Defaults to 10 as per wordpress' add_action().
     * @param int $acceptedArgs
     *   Number of args to handle. Defaults to 1 as per wordpress' add_action().
     */
    public function addAction($hook, $callback, $priority = 10, $acceptedArgs = 1)
    {
        $this->hookLoader->addAction($hook, $this, $callback, $priority, $acceptedArgs);
    }

    /**
     * Add filters for the service.
     */
    public function addFilters()
    {
    }

    /**
     * Add actions for the service.
     */
    public function addActions()
    {
    }

    /**
     * Render a named template.
     *
     * @param string $name
     *   Template name to apply.
     * @param array $vars
     *   Variables to be used with the template.
     */
    public function renderTemplate($name, array $vars = array())
    {
        $this->templateHandler->processTemplate($name, $vars);
    }
}
