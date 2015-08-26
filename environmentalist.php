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

// This will NOT be multisite compatible (see wp_load_core_site_options in wp_options.php)
if (!class_exists('WP_Environmentalist')) {
    class WP_Environmentalist {

        private $environmentName = 'default';
        private $environmentSuffix = '';

        public function __construct() {
            // Grab the name of the environment
            if (defined('WP_ENVIRONMENTALIST')){
                $this->environmentName = WP_ENVIRONMENTALIST;
            }

            $this->environmentSuffix = '_wpenvst_' . $this->environmentName;
            $this->setupHooks();
        }

        public function setupHooks() {

            // On load, we suck in the options, and for any with the environment's suffix, we add the option_<option_name>
            // filter to filter the value on get_option()

            $envOptionsSet = get_option('wpenvst_options_set' . $this->environmentSuffix);

            if (is_array($envOptionsSet)) {
                foreach ($envOptionsSet as $this_option) {
                    add_filter('option_' . $this_option, array($this, 'get_option'), 1, 1);
                }
            }

            add_action('updated_option', array($this, 'updated_option'), 1, 3);
            add_action('added_option', array($this, 'added_option'), 1, 2);

        }

        // On action add_option, we save the extra value with the environment suffix,
        // but only if we're not already saving something with the suffix!
        //
        // I've not optimised this - I've assumed that setting options isn't something that
        // happens a lot and is usually admin-side.
        public function added_option( $option, $value ) {
            if (! preg_match('/' . $this->environmentSuffix . '$/', $option)) {
                update_option($option . $this->environmentSuffix, $value);

                // Also, add the option to the list of options to filter
                $optionSet = get_option('wpenvst_options_set' . $this->environmentSuffix);
                if (! is_array($optionSet)) {
                    $optionSet = array();
                }
                if (!in_array($option, $optionSet)) {
                    $optionSet[] = $option;
                    update_option('wpenvst_options_set' . $this->environmentSuffix, $optionSet);
                }
            }
        }

        // On action update_option, we save the extra value with the environment suffix
        // but only if we're not already saving something with the suffix!
        public function updated_option( $option, $old_value, $value ) {
            $this->added_option( $option, $value );
        }

        public function get_option( $value ) {
            $thisFilter = current_filter();
            // This should only happen for options without the suffix., but check anyway
            if (preg_match('/option_.*' . $this->environmentSuffix . '$/', $thisFilter)) {
                return $value;
            }

            if (substr($thisFilter, 0, 7) == 'option_') {
                $thisOption = substr($thisFilter, 7);
            } else {
                // This should never happen
                return $value;
            }
            $newOptionName = $thisOption . $this->environmentSuffix;
            $newOptionValue = get_option( $newOptionName );
            if ($newOptionValue === false) {
                return $value;
            } else {
                return $newOptionValue;
            }
        }

    // On action delete_option, we delete... ?

    // Do we need to avoid doing this for transients? Probably not.

    }

    // On deactivate (or uninstall?) we need to copy all...?

    $environmentalist = new WP_Environmentalist();

}