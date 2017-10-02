<?php

namespace Sift\Practiceweb\Connectivity;

/**
 * Class Plugin.
 *
 * @package Sift\Practiceweb\Connectivity
 */
class Plugin
{
    /**
     * Hook loader object.
     *
     * @var HookLoader
     */
    protected $hookLoader;
    /**
     * Template handler object.
     *
     * @var TemplateHandler
     */
    protected $templateHandler;

    /**
     * Version of plugin.
     *
     * @var string
     */
    protected $version;
    /**
     * Root plugin path.
     *
     * @var string
     */
    protected $pluginRoot;
    /**
     * Registry to hold services added.
     *
     * @var array
     */
    protected $registry = array();

    /**
     * Plugin constructor.
     *
     * @param string $pluginRoot
     *   Base path this plugin lives in.
     */
    public function __construct($pluginRoot)
    {
        $this->version = '1.0.0';
        $this->hookLoader = new HookLoader();
        $this->templateHandler = new TemplateHandler($pluginRoot);
        $this->pluginRoot = $pluginRoot;
    }

    /**
     * Bootstrap the plugin.
     */
    public function run()
    {
        $this->hookLoader->run();
    }

    /**
     * Get the version number.
     *
     * @return string
     *   Version text.
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Register a service with the plugin.
     *
     * @param string $name
     *   Name of the service.
     * @param string $class
     *   Class of the service to instantiate.
     */
    public function registerService($name, $class)
    {
        $service = new $class(__FILE__, $this->hookLoader, $this->templateHandler);
        $this->registry[$name] = $service;
    }
}
