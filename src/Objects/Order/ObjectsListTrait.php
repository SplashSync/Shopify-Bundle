<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace   Splash\Connectors\Shopify\Objects\Order;

use DateTime;
use Splash\Connectors\Shopify\Models\ShopifyHelper as API;

/**
 * Shopify Product List Functions
 */
trait ObjectsListTrait
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function objectsList($filter = null, $params = null)
    {
        //====================================================================//
        // Prepare Parameters
        $query = array("status" => "any");
        if (isset($params["max"]) && ($params["max"] > 0) && isset($params["offset"]) && ($params["offset"] >= 0)) {
            $query = array(
                "status"    => "any",
                'limit'    =>   $params["max"],
                'page'     =>   (1 + (int) ($params["offset"] / $params["max"])),
            );
        }
        //====================================================================//
        // Execute Products List Request
        $rawData = API::get('orders', null, $query, 'orders');
        //====================================================================//
        // Request Failled
        if (null === $rawData) {
            return array( 'meta'    => array('current' => 0, 'total' => 0));
        }
        //====================================================================//
        // Compute Totals
        $response   =   array(
            'meta'  => array('current' => count($rawData), 'total' => API::count('orders')),
        );
        //====================================================================//
        // Parse Data in response
        foreach ($rawData as $order) {
            $response[]   = array(
                'id'                        =>      $order['id'],
                'name'                      =>      $order['name'],
                'created_at'                =>      (new DateTime($order['created_at']))->format(SPL_T_DATETIMECAST),
                'updated_at'                =>      (new DateTime($order['updated_at']))->format(SPL_T_DATETIMECAST),
                'processed_at'              =>      (new DateTime($order['processed_at']))->format(SPL_T_DATETIMECAST),
            );
        }

        return $response;
    }
}