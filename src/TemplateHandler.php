<?php

namespace Sift\Practiceweb\Connectivity;

/**
 * Class TemplateHandler.
 *
 * @package Sift\Practiceweb\Connectivity
 */
class TemplateHandler
{

    /**
     * Root location for templates.
     *
     * @var string
     */
    protected $templatesRoot;

    /**
     * TemplateHandler constructor.
     *
     * @param string $pluginPath
     *   Base path for the plugin. Used to generate paths.
     */
    public function __construct($pluginPath)
    {
        $this->templatesRoot = $pluginPath . '/templates';
    }

    /**
     * Simple template handler.
     *
     * @param string $name
     *   Name of template to find and load.
     * @param array $vars
     *   Variables array for the template.
     */
    public function processTemplate($name, array $vars = array())
    {
        $templatePath = sprintf('%s/%s.php', $this->templatesRoot, $name);
        if (file_exists($templatePath)) {
            require $templatePath;
        }
    }
}
