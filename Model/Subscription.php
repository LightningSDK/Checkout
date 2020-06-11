<?php

namespace lightningsdk\checkout\Model;

use lightningsdk\core\Model\BaseObject;
use lightningsdk\core\Tools\Database;

/**
 * Class Subscription
 * @package lightningsdk\checkout\Model
 *
 * @property integer subscription_id
 * @property integer user_id
 * @property string gateway_id
 * @property integer created
 * @property integer updated
 * @property string status
 */
class Subscription extends BaseObject {

    const TABLE = 'checkout_subscription';
    const PRIMARY_KEY = 'subscription_id';

    /**
     * @param $id
     * @return bool|Subscription
     * @throws \Exception
     */
    public static function loadByGatewayID($id) {
        $content = Database::getInstance()->selectRow(static::TABLE, ['gateway_id' => $id]);
        if ($content) {
            return new static($content);
        } else {
            return false;
        }
    }

    public function isActive() {
        return in_array($this->status, ['trialing', 'active']);
    }
}
