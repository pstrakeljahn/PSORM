<?php

use PS\Core\Database\DBConnector;
use PS\Core\Database\Entity;
use PS\Core\Database\Fields\EnumField;
use PS\Core\Database\Fields\IntegerField;
use PS\Core\Database\Fields\StringField;

require '../lib/core/init.php';

class UserPeerBasic extends Entity
{
    protected string $table = 'users';
    private static $name = 'User';
  
    public function __construct()
    {
        parent::__construct(static::$name, self::getFields());
    }
    
    public static function getFields(): array
    {
        return [
            (new StringField('firstName'))
                ->setNotNullable(true)
                ->setLength(45),
            (new IntegerField('age'))
                ->setUnsigned(true),
            (new EnumField('role'))
                ->setValues(array('admin', 'other'))
        ];
    }
}

$t = new UserPeerBasic;
$g = $t->getCreateTableSQL();
$db = new DBConnector();
$l = 2;