<?php

namespace PS\Core\Database;

class EntityMeta
{
    public string|null $entityName = null;
    public string|null $tabelName = null;
    public bool $withoutMeta = false;
    public bool $apiDisabled = false;
}
