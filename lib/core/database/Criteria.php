<?php

namespace PS\Core\Database;

/**
 * Class Criteria
 *
 * Utility class for building SQL conditions dynamically.
 */
class Criteria
{
    public const IN = "in";
    public const NOT_IN = "not in";
    public const PERCENT_LIKE_PERCENT = "% like %";
    public const LIKE_PERCENT = "like %";
    public const IS_NULL = "is null";
    public const IS_NOT_NULL = "is not null";
    public const ASC = "ASC";
    public const DESC = "DESC";

    /** @var string[] List of AND conditions */
    private array $conditions = [];

    /** @var array<int>|null LIMIT clause: [offset, length] */
    private ?array $limit = null;

    /** @var string|null ORDER BY clause */
    private ?string $orderBy = null;

    /** @var Criteria[] List of OR conditions as separate criteria */
    private array $orCriteria = [];

    /**
     * Static factory method to instantiate a Criteria object.
     *
     * @return self
     */
    public static function getInstace(): self
    {
        return new self();
    }

    /**
     * Adds a condition to the criteria.
     *
     * @param string $property Column name
     * @param string $value    Value to compare
     * @param string $operator SQL comparison operator
     *
     * @return $this
     */
    public function add(string $property, string $value, string $operator = "="): self
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

    /**
     * Adds a LIMIT clause to the query.
     *
     * @param int $offset Start position
     * @param int $length Number of rows to fetch
     *
     * @return $this
     */
    public function addLimit(int $offset, int $length): self
    {
        $this->limit = [$offset, $length];
        return $this;
    }

    /**
     * Adds an ORDER BY clause to the query.
     *
     * @param string $property Column name
     * @param string $direction Sorting direction (ASC|DESC)
     *
     * @return $this
     */
    public function addOrderBy(string $property, string $direction = self::ASC): self
    {
        $this->orderBy = sprintf("`%s` %s", $property, strtoupper($direction));
        return $this;
    }

    /**
     * Adds a nested OR criteria.
     *
     * @param Criteria $criteria
     *
     * @return $this
     */
    public function addCriteria(Criteria $criteria): self
    {
        $this->orCriteria[] = $criteria;
        return $this;
    }

    /**
     * Builds and returns the final SQL condition string.
     *
     * @return string SQL WHERE/ORDER BY/LIMIT clause
     */
    public function getConditions(): string
    {
        $clauses = [];

        // Combine AND conditions
        if (!empty($this->conditions)) {
            $clauses[] = implode(" AND ", $this->conditions);
        }

        // Combine OR sub-criteria
        if (!empty($this->orCriteria)) {
            $orClauses = [];
            foreach ($this->orCriteria as $orCriteria) {
                $sub = trim($orCriteria->getConditions());
                if (str_starts_with($sub, "WHERE ")) {
                    $sub = substr($sub, 6); // remove "WHERE "
                }
                $orClauses[] = "($sub)";
            }

            $clauses[] = implode(" OR ", $orClauses);
        }

        $sql = '';
        if (!empty($clauses)) {
            $sql .= "WHERE " . implode(" AND ", $clauses);
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . $this->orderBy;
        }

        if (!is_null($this->limit)) {
            $sql .= " LIMIT {$this->limit[0]}, {$this->limit[1]}";
        }

        return $sql;
    }
}
