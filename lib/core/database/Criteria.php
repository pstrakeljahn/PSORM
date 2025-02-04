<?php

namespace PS\Core\Database;

class Criteria
{
    const IN = "in";
    const NOT_IN = "not in";
    const PERCENT_LIKE_PERCENT = "% like %";
    const LIKE_PERCENT = "like %";
    const IS_NULL = "is null";
    const IS_NOT_NULL = "is not null";
    const ASC = "ASC";
    const DESC = "DESC";

    private array $conditions = [];
    private ?array $limit = null;
    private ?string $orderBy = null;
    private array $orCriteria = [];

    public static final function getInstace(): self
    {
        return new self;
    }

    public final function add(string $property, string $value, string $operator = "=")
    {
        switch ($operator) {
            case self::IN:
            case self::NOT_IN:
                $this->conditions[] = sprintf("`%s` %s (%s)", $property, $operator, $value);
                break;
            case self::PERCENT_LIKE_PERCENT:
                $this->conditions[] = sprintf("`%s` LIKE '%%%s%%'", $property, $value);
                break;
            case self::LIKE_PERCENT:
                $this->conditions[] = sprintf("`%s` LIKE '%s%%'", $property, $value);
                break;
            case self::IS_NULL:
                $this->conditions[] = sprintf("`%s` IS NULL", $property);
                break;
            case self::IS_NOT_NULL:
                $this->conditions[] = sprintf("`%s` IS NOT NULL", $property);
                break;
            default:
                $this->conditions[] = sprintf("`%s` %s '%s'", $property, $operator, $value);
                break;
        }
        return $this;
    }

    public final function addLimit(int $offset, int $length)
    {
        $this->limit = [$offset, $length];
        return $this;
    }

    public final function addOrderBy(string $property, string $direction = self::ASC)
    {
        $this->orderBy = sprintf("`%s` %s", $property, strtoupper($direction));
        return $this;
    }

    public final function addCriteria(Criteria $criteria)
    {
        $this->orCriteria[] = $criteria;
        return $this;
    }

    public final function getConditions(): string
    {
        if (!count($this->conditions) && !count($this->limit ?? []) && !count($this->orCriteria)) return '';
        $sql = '';
        $hasWhereCondition = false;
        if (!empty($this->orCriteria)) {
            $sql = implode(" AND ", $this->conditions);
            $hasWhereCondition = true;
        }
        if (!empty($this->orCriteria)) {
            $orConditions = [];
            foreach ($this->orCriteria as $orCriteria) {
                $orConditions[] = "(" . $orCriteria->getConditions() . ")";
            }
            $sql .= " OR " . implode(" OR ", $orConditions);
            $hasWhereCondition = true;
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . $this->orderBy;
        }

        if (!is_null($this->limit)) {
            $sql .= " LIMIT " . $this->limit[0] . ", " . $this->limit[1];
        }
        return $hasWhereCondition ? "WHERE " : "" . $sql;
    }
}
