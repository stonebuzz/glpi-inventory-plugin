<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * Copyright (C) 2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on FusionInventory for GLPI
 * Copyright (C) 2010-2021 by the FusionInventory Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI Inventory Plugin.
 *
 * GLPI Inventory Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

//Options for GLPI 0.71 and newer : need slave db to access the report
$USEDBREPLICATE = 1;
$DBCONNECTION_REQUIRED = 0;

include("../../../inc/includes.php");

Html::header(__('FusionInventory', 'glpiinventory'), $_SERVER['PHP_SELF'], "utils", "report");

Session::checkRight('computer', READ);

$nbdays = filter_input(INPUT_GET, "nbdays");
if ($nbdays == '') {
    $nbdays = 365;
}

$state = filter_input(INPUT_GET, "state");
if (!is_numeric($state)) {
    $state = 0;
}

echo "<form action='" . filter_input(INPUT_SERVER, "PHP_SELF") . "' method='get'>";
echo "<table class='tab_cadre' cellpadding='5'>";

echo "<tr>";
echo "<th colspan='2'>";
echo __('Computers not inventoried since xx days', 'glpiinventory');
echo "</th>";
echo "</tr>";

echo "<tr class='tab_bg_1' align='center'>";
echo "<td>";
echo __('Number of days (minimum) since last inventory', 'glpiinventory') . " :&nbsp;";
echo "</td>";
echo "<td>";
Dropdown::showNumber("nbdays", [
                'value' => $nbdays,
                'min'   => 1,
                'max'   => 365]);
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_1' align='center'>";
echo "<td>";
echo __('Status');
echo "</td>";
echo "<td>";
Dropdown::show("State", ['name' => 'state', 'value' => $state]);
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_2'>";
echo "<td align='center' colspan='2'>";
echo "<input type='submit' value='" . __('Validate') . "' class='submit' />";
echo "</td>";
echo "</tr>";

echo "</table>";
Html::closeForm();

$computer = new Computer();

$state_sql = "";
if (($state != "") and ($state != "0")) {
    $state_sql = " AND `states_id` = '" . $state . "' ";
}

$query = "SELECT `last_inventory_update`, `computers_id`
      FROM `glpi_plugin_glpiinventory_inventorycomputercomputers`
   LEFT JOIN `glpi_computers` ON `computers_id`=`glpi_computers`.`id`
WHERE ((NOW() > ADDDATE(last_inventory_update, INTERVAL " . $nbdays . " DAY)
      OR last_inventory_update IS NULL)
   " . $state_sql . ")" . getEntitiesRestrictRequest("AND", "glpi_computers") . "

ORDER BY last_inventory_update DESC";

$result = $DB->query($query);

echo "<table class='tab_cadre_fixe' cellpadding='5' width='950'>";

echo "<tr class='tab_bg_1'>";
echo "<th colspan='5'>" . __('Number of items') . " : " . $DB->numrows($result) . "</th>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<th>" . __('Name') . "</th>";
echo "<th>" . __('Last inventory', 'glpiinventory') . "</th>";
echo "<th>" . __('Serial Number') . "</th>";
echo "<th>" . __('Inventory number') . "</th>";
echo "<th>" . __('Status') . "</th>";
echo "</tr>";

while ($data = $DB->fetchArray($result)) {
    echo "<tr class='tab_bg_1'>";
    echo "<td>";
    $computer->getFromDB($data['computers_id']);
    echo $computer->getLink(1);
    echo "</td>";
    echo "<td>" . Html::convDateTime($data['last_inventory_update']) . "</td>";
    echo "<td>" . $computer->fields['serial'] . "</td>";
    echo "<td>" . $computer->fields['otherserial'] . "</td>";
    echo "<td>";
    echo Dropdown::getDropdownName(getTableForItemType("State"), $computer->fields['states_id']);
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

Html::footer();
