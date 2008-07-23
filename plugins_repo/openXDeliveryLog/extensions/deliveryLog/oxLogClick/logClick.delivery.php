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

###START_STRIP_DELIVERY
/**
 * Dependencies between the plugins - used to set the order in which the components
 * are executed by delivery engine when calling components to log the data.
 */
$GLOBALS['_MAX']['pluginsDependencies']['deliveryLog:ox_click:ox_click'] = array(
    'deliveryDataPrepare:ox_core:ox_core',
);
###END_STRIP_DELIVERY

function Plugins_deliveryLog_ox_click_ox_click_Delivery_logClick()
{
    $data = $GLOBALS['_MAX']['deliveryData'];
    $aQuery = array(
        'interval_start' => $data['interval_start'],
        'creative_id'    => $data['creative_id'],
        'zone_id'        => $data['zone_id'],
    );
    return MAX_bucket_updateTable('data_bucket_click', $aQuery);
}

?>