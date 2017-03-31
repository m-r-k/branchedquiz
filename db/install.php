<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    mod_branchedquiz
 * @copyright  2017 onwards Dominik Wittenberg, Paul Youssef, Pavel Azanov, Allessandro Noli, Robin Voigt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post installation procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_branchedquiz_install() {
    global $DB;
    $pfx = $DB->get_prefix();
    $DB->execute('DROP TABLE IF EXISTS '.$pfx.'branchedquiz');
    $DB->execute('CREATE OR REPLACE VIEW '.$pfx.'branchedquiz AS SELECT * FROM '.$pfx.'quiz');
}

/**
 * Post installation recovery procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_branchedquiz_install_recovery() {
    global $DB;
    $pfx = $DB->get_prefix();
    $DB->execute('DROP TABLE IF EXISTS '.$pfx.'branchedquiz');
    $DB->execute('DROP TABLE IF EXISTS '.$pfx.'branchedquiz_edge');
    $DB->execute('DROP TABLE IF EXISTS '.$pfx.'branchedquiz_node');
}
