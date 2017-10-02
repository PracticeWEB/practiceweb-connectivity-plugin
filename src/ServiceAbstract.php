<?php

namespace Sift\Practiceweb\Connectivity;

use Sift\Practiceweb\Connectivity\HookLoader;
use Sift\Practiceweb\Connectivity\TemplateHandler;

/**
 * Class ServiceAbstract.
 *
 * @package Sift\Practiceweb\Connectivity
 */
abstract class ServiceAbstract
{

    /**
     * Plugin file which registered the service.
     *
     * @var String
     */
    protected $pluginFile;

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
    public function __construct($pluginFile, HookLoader $loader, TemplateHandler $handler)
    {
        $this->pluginFile = $pluginFile;
        $this->hookLoader = $loader;
        $this->templateHandler = $handler;
        $this->addFilters();
        $this->addActions();
        $this->createPostTypes();
        $this->addActivationHooks();
        $this->addDeactivationHooks();
        $this->addShortCodes();
        $this->addWidgets();
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
     * Add activation hooks.
     */
    public function addActivationHooks()
    {
    }

    /**
     * Add deactivation hooks.
     */
    public function addDeactivationHooks()
    {
    }

    /**
     * Create any post types.
     */
    public function createPostTypes()
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

    /**
     * Add any shortcodes.
     */
    public function addShortcodes()
    {
    }

    /**
     * Add any widgets.
     */
    public function addWidgets()
    {
    }
}
