<?php

namespace PS\Core\_devtools\Helper;

use PS\Core\Database\Criteria;

class ApiHelper
{
    public static final function findObject(string $objName, $id = null): ?array
    {
        $peerClass = "ObjectPeer\\" . $objName . "Peer";
        if(!is_null($id)) {
            return $peerClass::findById($id, true);    
        } else {
            return $peerClass::find(Criteria::getInstace(), true);
        }
    }
}
