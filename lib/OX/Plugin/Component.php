<?php

/*
+---------------------------------------------------------------------------+
| OpenX v${RELEASE_MAJOR_MINOR}                                                                |
| =======${RELEASE_MAJOR_MINOR_DOUBLE_UNDERLINE}                                                                |
|                                                                           |
| Copyright (c) 2003-2008 OpenX Limited                                     |
| For contact details, see: http://www.openx.org/                           |
|                                                                           |
| This program is free software; you can redistribute it and/or modify      |
| it under the terms of the GNU General Public License as published by      |
| the Free Software Foundation; either version 2 of the License, or         |
| (at your option) any later version.                                       |
|                                                                           |
| This program is distributed in the hope that it will be useful,           |
| but WITHOUT ANY WARRANTY; without even the implied warranty of            |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
| GNU General Public License for more details.                              |
|                                                                           |
| You should have received a copy of the GNU General Public License         |
| along with this program; if not, write to the Free Software               |
| Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
+---------------------------------------------------------------------------+
$Id$
*/

require_once MAX_PATH . '/lib/max/Plugin.php';

define('OX_COMPONENT_SUFFIX', '.class.php');

/**
 * OX_Component is a static helper class for dealing with plugins/components. It
 * provides a factory method for including/instantiating components, and
 * provides methods for:
 *  - Reading groups of components from an extension or from an extension/group;
 *  - Calling component methods;
 *
 * Note: This class was taken from lib/max/Plugin.php
 *
 * @static
 * @package    OpenXPlugin
 * @author     Chris Nutting <chris.nutting@openx.org>
 * @author     Andrew Hill <andrew@openx.org>
 * @author     Radek Maciaszek <radek@openx.org>
 */
class OX_Component
{
    var $extension;
    var $group;
    var $component;
    var $enabled;

    /**
     * A factory method, for including and instantiating a component, given an
     * extension/group (and optional component name).
     *
     * @static
     * @param string $extension The plugin extension name (i.e. /plugins/<extension> directory).
     * @param string $group The component group name (i.e. /plugins/<extension>/<group> directory).
     * @param string $component Optional name of the PHP file which contains the component,
     *                     otherwise the component with the same name as the group is assumed.
     * @todo There is currently a mechanism in place to not include components from groups which
     *       haven't been enabled in the configuration file, as more extensions are refactored,
     *       they should be added to the refactoredExtensions until this whole section can be removed
     * @return mixed The instantiated component object, or false on error.
     */
    function &factory($extension, $group, $component = null)
    {
        if ($component === null) {
            $component = $group;
        }
        if (!OX_Component::_includeComponentFile($extension, $group, $component))
        {
            return false;
        }
        $className = OX_Component::_getComponentClassName($extension, $group, $component);
        $obj = new $className($extension, $group, $component);
        $obj->extension = $extension;
        $obj->group     = $group;
        $obj->component = $component;
        $obj->enabled   = true;
        if (!OX_Component::_isEnabledComponent($extension, $group, $component))
        {
            $obj->enabled = false;
        }
        return $obj;
    }

    function &factoryByComponentIdentifier($componentIdentifier)
    {
        list($extension, $group, $component) = OX_Component::parseComponentIdentifier($componentIdentifier);
        return OX_Component::factory($extension, $group, $component);
    }

    function _isEnabledComponent($extension, $group, $component)
    {
        $aRefactoredExtensions = array('deliveryLimitations', 'bannerTypeHtml', 'bannerTypeText');
        if (in_array($extension, $aRefactoredExtensions))
        {
            $aConf = $GLOBALS['_MAX']['CONF'];
            if (empty($aConf['pluginGroupComponents'][$group]))
            {
                return false;
            }
            if (!$aConf['pluginGroupComponents'][$group])
            {
                return false;
            }
        }
        return true;
    }

    /**
     * A private method to include a component class file, given an extension/group
     * (and optional component name).
     *
     * @static
     * @access private
     * @param string $extension The plugin extension (i.e. /plugins/<extension> directory).
     * @param string $group The component group name (i.e. /plugins/<extension>/<group> directory).
     * @param string $component Optional name of the PHP file which contains the component,
     *                     otherwise the component with the same name as the group is assumed.
     * @return boolean True on success, false otherwise.
     */
    function _includeComponentFile($extension, $group, $component = null)
    {
        $aConf = $GLOBALS['_MAX']['CONF'];
        if ($component === null) {
            $component = $group;
        }
        $groupPath = empty($group) ? "" : $group."/";

        $fileName = MAX_PATH . $aConf['pluginPaths']['extensions'] . $extension . "/". $groupPath . $component . OX_COMPONENT_SUFFIX;
        if (!file_exists($fileName)) {
            //MAX::raiseError("Unable to include the file $fileName.");
            return false;
        } else {
            include_once $fileName;
        }
        $className = OX_Component::_getComponentClassName($extension, $group, $component);
        if (!class_exists($className)) {
            MAX::raiseError("Component file included but class '$className' does not exist.");
            return false;
        } else {
            return true;
        }
    }

    /**
     * A private method for generating the (expected) class name of a component.
     *
     * @static
     * @access private
     * @param string $extension The plugin extension name (i.e. /plugins/<extension> directory).
     * @param string $group The component group name (i.e. /plugins/<extension>/<group> directory).
     * @param string $component Optional name of the PHP file which contains the component,
     *                     otherwise the component with the same name as the group
     *                     is assumed.
     * @return string The component class name.
     */
    function _getComponentClassName($extension, $group, $component = null)
    {
        if ($component === null) {
            $component = $group;
        }
        $className = 'Plugins_' . ucfirst($extension) . '_' . ucfirst($group) . '_' . ucfirst($component);
        return $className;
    }

    /**
     * A method to return an array of component objects from a selected plugin extension
     * or extension/group.
     *
     * @static
     * @param string $extension The plugin extension name (i.e. /plugins/<extension> directory).
     * @param string $group An optional component group name (i.e. /plugins/<extension>/<group>
     *                        directory). If not given, the search for component files will start
     *                        at the extension directory level.
     * @param boolean $onlyComponentNameAsIndex If true, the array index for the components is
     *                                       "componentName", otherwise the index is of the
     *                                       format is "groupName:componentName".
     * @param mixed $recursive If the boolean 'true', returns all components in the
     *                         given extension (and group, if specified), and all
     *                         subdirectories thereof.
     *                         If an integer, returns all components in the given
     *                         extension (and group, if specified) and subdirectories
     *                         thereof, down to the depth specified by the parameter.
     * @param boolean $enabledOnly Only return components which are enabled
     * @return array An array of component objects, indexed as specified by the
     *               $onlyComponentNameAsIndex parameter.
     */
    function &getComponents($extension, $group = null, $onlyComponentNameAsIndex = true, $recursive = 1, $enabledOnly = true)
    {
        $aComponents = array();
        $aComponentFiles = OX_Component::_getComponentsFiles($extension, $group, $recursive);
        foreach ($aComponentFiles as $key => $componentFile) {
            $aComponentInfo = explode(':', $key);
            if (count($aComponentInfo) > 1) {
                $component = OX_Component::factory($extension, $aComponentInfo[0], $aComponentInfo[1]);
                if ($component !== false && (!$enabledOnly || $component->enabled == true)) {
                    if ($onlyComponentNameAsIndex) {
                        $aComponents[$aComponentInfo[1]] = $component;
                    } else {
                        $aComponents[$key] = $component;
                    }
                }
            }
        }
        return $aComponents;
    }

    /**
     * A private method to return a list of component files in a given plugin extension,
     * or a given extension/group.
     *
     * @static
     * @access private
     * @param string $extension The plugin extension name (i.e. /plugins/<extension> directory).
     * @param string $group An optional component group name (i.e. /plugins/<extension>/<group>
     *                        directory). If not given, the search for component files will
     *                        start at the extension directory level.
     * @param mixed $recursive If the boolean 'true', returns all component files in the
     *                         given directory and all subdirectories.
     *                         If an integer, returns all component files in the given
     *                         directory and subdirectories down to the depth
     *                         specified by the parameter.
     * @return array An array of the component files found, indexed by "directory:filename",
     *               where "directory" is the relative directory path below the
     *               given directory parameter, and "filename" is the filename
     *               before the OX_COMPONENT_SUFFIX extension of the file.
     */
    function _getComponentsFiles($extension, $group = null, $recursive = 1)
    {
        $aConf = $GLOBALS['_MAX']['CONF'];
        $pluginsDir = MAX_PATH . $aConf['pluginPaths']['extensions'];

        if (!empty($group)) {
            $dir = $pluginsDir . '/' . $extension . '/' . $group;
        } else {
            $dir = $pluginsDir . '/' . $extension;
        }
        return OX_Component::_getComponentFilesFromDirectory($dir, $recursive);
    }

    /**
     * A private method to return a list list of files from a directory
     * (and subdirectories, if appropriate)  which match the defined
     * plugin file mask (MAX_PLUGINS_FILE_MASK).
     *
     * @static
     * @access private
     * @param string $directory The directory to search for files in.
     * @param mixed $recursive If the boolean 'true', returns all files in the given
     *                         directory and all subdirectories that match the file
     *                         mask.
     *                         If an integer, returns all files in the given
     *                         directory and subdirectories down to the depth
     *                         specified by the parameter that match the file mask.
     * @return array An array of the files found, indexed by "directory:filename",
     *               where "directory" is the relative directory path below the
     *               given directory parameter, and "filename" is the filename
     *               before the OX_COMPONENT_SUFFIX extension of the file.
     */
    function _getComponentFilesFromDirectory($directory, $recursive = 1)
    {
        if (is_readable($directory)) {
            $fileMask = OX_Component::_getFileMask();
            $oFileScanner = new MAX_FileScanner();
            $oFileScanner->addFileTypes(array('php','inc'));
            $oFileScanner->setFileMask($fileMask);
            $oFileScanner->addDir($directory, $recursive);
            return $oFileScanner->getAllFiles();
        } else {
            return array();
        }
    }

    function _getFileMask()
    {
        return '^.*' . $GLOBALS['_MAX']['CONF']['pluginPaths']['extensions'] . '/?([a-zA-Z0-9\-_]*)/?([a-zA-Z0-9\-_]*)?/([a-zA-Z0-9\-_]*)'.preg_quote(OX_COMPONENT_SUFFIX).'$';
    }

    /**
     * A method to include a component, and call a method statically on the component class.
     *
     * @static
     * @param string $extension The plugin extension name (i.e. /plugins/<extension> directory).
     * @param string $group The plugin package name (i.e. /plugins/<extension>/<group>
     *                        directory).
     * @param string $component Optional name of the PHP file which contains the component class,
     *                     otherwise the component with the same name as the group
     *                     is assumed.
     * @param string $staticMethod The name of the method of the component class to call statically.
     * @param array $aParams An optional array of parameters to pass to the method called.
     * @return mixed The result of the static method call, or false on failure to include
     *               the plugin.
     */
    function &callStaticMethod($extension, $group, $component = null, $staticMethod, $aParams = null)
    {
        if ($component === null) {
            $component = $group;
        }
        if (!OX_Component::_isEnabledComponent($extension, $group, $component))
        {
            return false;
        }
        if (!OX_Component::_includeComponentFile($extension, $group, $component)) {
            return false;
        }
        $className = OX_Component::_getComponentClassName($extension, $group, $component);

        // PHP4/5 compatibility for get_class_methods.
        $aClassMethods = array_map(strtolower, (get_class_methods($className)));
        if (!$aClassMethods) {
            $aClassMethods = array();
        }
        if (!in_array(strtolower($staticMethod), $aClassMethods)) {
            MAX::raiseError("Method '$staticMethod()' not defined in class '$className'.", MAX_ERROR_INVALIDARGS);
            return false;
        }
        if (is_null($aParams)) {
            return call_user_func(array($className, $staticMethod));
        } else {
            return call_user_func_array(array($className, $staticMethod), $aParams);
        }
    }

    /**
     * A method to run a method on all component objects in an array of components.
     *
     * @static
     * @param array $aPComponents An array of component objects.
     * @param string $methodName The name of the method to executed for every component.
     * @param array $aParams An optional array of parameters to pass to the method called.
     * @return mixed An array of the results of the method calls, or false on error.
     */
    function &callOnComponents(&$aComponents, $methodName, $aParams = null)
    {
        if (!is_array($aComponents)) {
            MAX::raiseError('Bad argument: Not an array of components.', MAX_ERROR_INVALIDARGS);
            return false;
        }
        foreach ($aComponents as $key => $oComponent) {
            if (!is_a($oComponent, 'OX_Component_Common')) {
                MAX::raiseError('Bad argument: Not an array of components.', MAX_ERROR_INVALIDARGS);
                return false;
            }
        }
        $aReturn = array();
        foreach ($aComponents as $key => $oComponent) {
            // Check that the method name can be called
            if (!is_callable(array($oComponent, $methodName))) {
                $message = "Method '$methodName()' not defined in class '" .
                            OX_Component::_getComponentClassName($oComponent->extension, $oComponent->group, $oComponent->component) . "'.";
                MAX::raiseError($message, MAX_ERROR_INVALIDARGS);
                return false;
            }
            if (is_null($aParams)) {
                $aReturn[$key] = call_user_func(array($aComponents[$key], $methodName));
            } else {
                $aReturn[$key] = call_user_func_array(array($aComponents[$key], $methodName), $aParams);
            }
        }
        return $aReturn;
    }

    /**
     * A method to run one method on all the components in a group where the component
     * has the specified type and component hook point. For use with Maintenance
     * plugins only.
     *
     * @static
     * @param array $aPlugins An array of plugin objects.
     * @param string $methodName The name of the method to executed for every plugin
     *                           that should be run.
     * @param integer $type Either MAINTENANCE_PLUGIN_PRE or MAINTENANCE_PLUGIN_POST.
     * @param integer $hook A maintenance plugin hook point. For example,
     *                      MSE_PLUGIN_HOOK_summariseIntermediateRequests.
     * @param array $aParams An optional array of parameters to pass to the method
     *                       called for every plugin that should be run.
     * @return boolean True, except when $type is MAINTENANCE_PLUGIN_PRE, and at least
     *                 one of the plugins is a replacement plugin for a standard
     *                 maintenance engine task (that is, at least one of the plugins
     *                 had a run() method that returned false).
     */
    function &callOnComponentsByHook(&$aComponents, $methodName, $type, $hook, $aParams = null)
    {
        if (!is_array($aComponents)) {
            MAX::raiseError('Bad argument: Not an array of components.', MAX_ERROR_INVALIDARGS);
            return false;
        }
        foreach ($aComponents as $key => $oComponent) {
            if (!is_a($oComponent, 'OX_Component_Common')) {
                MAX::raiseError('Bad argument: Not an array of components.', MAX_ERROR_INVALIDARGS);
                return false;
            }
        }
        $return = true;
        foreach ($aComponents as $key => $oComponent) {
            // Ensure the plugin is a maintenance plugin
            if (is_a($oComponent, 'Plugins_Maintenance')) {
                // Check that the method name can be called
                if (!is_callable(array($oComponent, $methodName))) {
                    MAX::raiseError("Method '$methodName()' not defined in class '".get_class($oComponent)."'.", MAX_ERROR_INVALIDARGS);
                    return false;
                }
                // Check that the the plugin's type and hook match
                if (($oComponent->getHookType() == $type) && ($oComponent->getHook() == $hook)) {
                    if (is_null($aParams)) {
                        $methodReturn = call_user_func(array($aComponents[$key], $methodName));
                    } else {
                        $methodReturn = call_user_func_array(array($aComponents[$key], $methodName), $aParams);
                    }
                    if ($methodReturn === false) {
                        $return = false;
                    }
                }
            }
        }
        return $return;
    }

    function getComponentIdentifier()
    {
        return implode(':', array($this->extension, $this->group, $this->component));
    }

    function parseComponentIdentifier($componentIdentifier)
    {
        return explode(':', $componentIdentifier);
    }

    /**
     * This method gets the handler that should be used for a particulat extension if the component
     * doesn't provide it's own specific handler
     *
     * @param string $extension The extension to get the fallback handler for
     * @return object The handler object
     */
    function &getFallbackHandler($extension)
    {
        $path = $GLOBALS['_MAX']['CONF']['pluginPaths']['extensions'].$extension.'/';
        $fileName = MAX_PATH.$path.$extension.'.php';
        if (!file_exists($fileName))
        {
            MAX::raiseError("Unable to include the file $fileName.");
            return false;
        }
        include_once $fileName;
        $className  = 'Plugins_'.$extension;
        if (!class_exists($className))
        {
            MAX::raiseError("Plugin file included but class '$className' does not exist.");
            return false;
        }
        $oPlugin = new $className();
        $oPlugin->extension = $extension;
        $oPlugin->enabled   = false;
        return $oPlugin;
    }

}

?>