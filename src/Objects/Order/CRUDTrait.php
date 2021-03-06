<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Connectors\Shopify\Objects\Order;

use ArrayObject;
use Splash\Connectors\Shopify\Helpers\HappyCommerceHelper;
use Splash\Connectors\Shopify\Helpers\MondialRelayHelper;
use Splash\Connectors\Shopify\Models\ShopifyHelper as API;
use Splash\Core\SplashCore      as Splash;

/**
 * Shopify Order CRUD Functions
 */
trait CRUDTrait
{
    /**
     * Load Request Object
     *
     * @param string $objectId Object id
     *
     * @return ArrayObject|false
     */
    public function load($objectId)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Get Customer Order from Api
        $object = API::get("orders", $objectId, array(), "order");
        //====================================================================//
        // Fetch Object from Shopify
        if (null === $object) {
            return Splash::log()->errTrace("Unable to load Order/Invoice (".$objectId.").");
        }
        //====================================================================//
        // Override Order Infos with Apps Informations
        if ($this->connector->hasHappyColissimoPlugin()) {
            HappyCommerceHelper::apply($object, $this->getMetadataFromApi(
                "orders/".$objectId."/metafields",
                HappyCommerceHelper::NAMESPACE,
                HappyCommerceHelper::KEY,
            ));
        }
        if ($this->connector->hasMondialRelayPlugin()) {
            MondialRelayHelper::apply($object);
        }

        return new ArrayObject($object, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Create Request Object
     *
     * @return false
     */
    public function create()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        return Splash::log()->err("Splash API Cannot Create Shopify Orders!");
    }

    /**
     * Update Request Object
     *
     * @param bool $needed Is This Update Needed
     *
     * @return bool|string Object Id
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function update($needed)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Order Information Update is Forbidden
        if ($this->isToUpdate('fulfillments')) {
            if (method_exists($this, 'updateFulfillment')) {
                $this->updateFulfillment();
            }
        }

        //====================================================================//
        // Order Information Update is Forbidden
        if ($needed) {
            return Splash::log()->err("Splash API Cannot Update Shopify Orders!");
        }

        return $this->getObjectIdentifier();
    }

    /**
     * Delete requested Object
     *
     * @param string $objectId Object Id.
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function delete($objectId = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        return Splash::log()->err("Splash API Cannot Delete Shopify Orders!");
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdentifier()
    {
        if (!isset($this->object->id)) {
            return false;
        }

        return (string) $this->object->id;
    }
}
