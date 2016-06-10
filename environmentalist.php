<?php
/*
Plugin Name: Environmentalist
Plugin URI: http://
Description: Per-environment options for WordPress.
Version: 0.1
Author: Ross Wintle/Oikos
Author Email: ross@oikos.org.uk
License:

  Copyright 2015 Ross Wintle/Oikos Digital Ltd (ross@oikos.org.uk)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* ARGH THIS WHOLE THING IS BROKEN BECAUSE IT CAN'T HANDLE OPTION NAMES > 64 CHARS!!!

   OK, SO THE PLAN IS THIS:
   - Instead of storing options as <option_name>_<suffix>, we'll have to store them
     in the options_set array, with their values.

   REFACTOR TIME!!!
*/

// This will NOT be multisite compatible (see wp_load_core_site_options in wp_options.php)
if (!class_exists('WP_Environmentalist')) {
    class WP_Environmentalist {


        private $defaultEnvironmentName = 'default';
        private $defaultEnvironmentOptionsSetName = '';
        private $defaultEnvOptionsSet = array();
        private $environmentName = 'default';
        private $environmentOptionsSetName = '';
        private $envOptionsSet = array();

        public function __construct() {
            // Grab the name of the environment
            if (defined('WP_ENVIRONMENTALIST')){
                $this->environmentName = WP_ENVIRONMENTALIST;
            }

            $this->setupHooks();
        }

        public function setupHooks() {

            // On load, we suck in the options, and for any with the environment's suffix, we add the option_<option_name>
            // filter to filter the value on get_option()

            $this->environmentOptionsSetName = 'wpenvst_options_set_' . $this->environmentName;
            $this->defaultEnvironmentOptionsSetName = 'wpenvst_options_set_' . $this->defaultEnvironmentName;
            
            $this->envOptionsSet = get_option( $this->environmentOptionsSetName );
            $this->defaultEnvOptionsSet = get_option( $this->defaultEnvironmentOptionsSetName );

            if ( !is_array($this->envOptionsSet) ) {
                $this->envOptionsSet = array();
            }

            if ( !is_array($this->defaultEnvOptionsSet) ) {
                $this->defaultEnvOptionsSet = array();
            }

            foreach ($this->envOptionsSet as $thisOptionName => $thisOption) {
                add_filter('option_' . $thisOptionName, array($this, 'get_option'), 1, 1);
            }

            add_action('add_option', array($this, 'pre_add_option'), 1, 2);
            add_action('update_option', array($this, 'pre_update_option'), 1, 3);
            add_action('updated_option', array($this, 'updated_option'), 1, 3);
            add_action('added_option', array($this, 'added_option'), 1, 2);

        }

        private function isOptionsSetName( $optionName ) {
            return strpos($optionName, 'wpenvst_options_set_') === 0;
        }

        public function isDefaultEnvironment() {
            return $this->environmentName == $this->defaultEnvironmentName;
        }

        //
        // This fires immediately before an option is updated. We hook into this because
        // we save the option every time to the standard (non-suffixed) option name
        // so if we are in a non-default environment, we will lose the non-default option
        // (we would write the environment's value to both the non-suffixed option and
        // to the environment-suffixed option).
        //
        // In this hook we get around this by saving the current non-suffixed option's value
        // to the default (suffixed!) environment. We must be sure to add the option name to
        // the default environment's options set too!
        //
        // We should ONLY do this if:
        // a) we're in a non-default environment (though, actually, if we're in the default
        //    environment it doesn't really matter)
        // b) we're not saving a suffixed value
        //
        // The same is true for adding options, so use the same code.
        //
        public function pre_update_option( $option, $old_value, $value ) {
            $this->pre_add_option( $option, $value );
        }

        public function pre_add_option( $option, $value ) {
            if (! $this->isDefaultEnvironment() && ! $this->isOptionsSetName( $option )) {
                $currentValue = $this->getDefaultValue( $option );
                //xdebug_break();
                //echo "Setting default value " . $currentValue . " for option " . $option . "\n";
                $this->setDefaultValue( $option, $currentValue);
            }
        }

        //
        // This works because:
        // a) if no option exists in the default environment options set, the base, non-suffixed value will be retrieved
        // b) if an option exists in the current non-default environment options set, then an option exist in the
        //    default options set and will be retrieved
        // There is (should be?) never a case where this drop through to get_option and a
        // non-default environment-specific version is retrieved.
        //
        private function getDefaultValue( $option ) {
            if ( $this->isOptionsSetName( $option ) ) {
                $default_value = get_option( $option );
            } else {
                if ( isset( $this->defaultEnvOptionsSet[ $option ] ) ) {
                    $defaultValue = $this->defaultEnvOptionsSet[ $option ];
                } else {
                    $defaultValue = get_option( $option );
                }
            }
            return $defaultValue;
        }

        //
        // Adds a default environment option value.
        //
        private function setDefaultValue( $option, $value ) {
            if ( $this->isOptionsSetName( $option ) ) {
                update_option( $option, $value );
            } else {
                $this->addToOptionSet( $option, $value, $this->defaultEnvOptionsSet, $this->defaultEnvironmentOptionsSetName );
            }
        }

        //
        // Adds an option name to the options set and saves it to the DB
        //
        private function addToOptionSet( $option, $value, &$optionsSet, $optionsSetName ) {
            if (! is_array($optionsSet)) {
                $optionsSet = array();
            }

            if (! $this->isOptionsSetName( $option ) ) {
                $optionsSet[ $option ] = $value;
                update_option( $optionsSetName, $optionsSet );
            }

        }

        // On action add_option, we save the extra value with the environment suffix,
        // but only if we're not saving an options set!
        //
        // I've not optimised this - I've assumed that setting options isn't something that
        // happens a lot and is usually admin-side.
        public function added_option( $option, $value ) {
            if (! $this->isOptionsSetName( $option )) {
                $this->addToOptionSet( $option, $value, $this->envOptionsSet, $this->environmentOptionsSetName );
            }
        }

        // On action update_option, we save the extra value with the environment suffix
        // but only if we're not already saving something with the suffix!
        public function updated_option( $option, $old_value, $value ) {
            $this->added_option( $option, $value );
        }

        public function get_option( $value ) {
            $thisFilter = current_filter();

            // Don't process option sets!
            if (preg_match('/option_wpenvst_options_set_.*$/', $thisFilter)) {
                return $value;
            }
            if (substr($thisFilter, 0, 7) == 'option_') {
                $thisOption = substr($thisFilter, 7);
            } else {
                // This should never happen
                return $value;
            }
            //echo "Getting option value for $thisOption\n";
            if ( isset( $this->envOptionsSet[ $thisOption ] ) ) {
                //echo "Getting modified option value for $thisOption\n";
                $newOptionValue = $this->envOptionsSet[ $thisOption ];
            } else {
                $newOptionValue = $value;
            }
            return $newOptionValue;
        }

    // On action delete_option, we delete... ?

    // Do we need to avoid doing this for transients? Probably not.

    }

    // On deactivate (or uninstall?) we need to copy all...?

    $environmentalist = new WP_Environmentalist();

}