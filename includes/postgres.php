<?php

function createPostgresPdo(array $dbConfig): PDO
{
    $databaseUrl = $dbConfig['url'] ?? '';
    if ($databaseUrl !== '') {
        $parts = parse_url($databaseUrl);
        if (
            $parts === false
            || !isset($parts['scheme'], $parts['host'], $parts['path'])
            || !in_array($parts['scheme'], ['postgres', 'postgresql'], true)
        ) {
            throw new RuntimeException('Invalid PostgreSQL connection URL.');
        }

        $dsn = 'pgsql:host=' . $parts['host']
            . ';port=' . (int)($parts['port'] ?? 5432)
            . ';dbname=' . rawurldecode(ltrim($parts['path'], '/'));
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            if (isset($query['sslmode'])) {
                $dsn .= ';sslmode=' . $query['sslmode'];
            }
        }
        $username = rawurldecode($parts['user'] ?? '');
        $password = rawurldecode($parts['pass'] ?? '');
    } else {
        $dsn = 'pgsql:host=' . $dbConfig['host']
            . ';port=' . (int)$dbConfig['port']
            . ';dbname=' . $dbConfig['database'];
        if ($dbConfig['sslmode'] !== '') {
            $dsn .= ';sslmode=' . $dbConfig['sslmode'];
        }
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];
    }

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

final class PgConnection
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function prepare(string $sql): PgStatement
    {
        return new PgStatement($this, $this->pdo->prepare($sql));
    }

    public function query(string $sql): PgResult
    {
        return new PgResult($this->pdo->query($sql));
    }

    public function begin_transaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'insert_id') {
            return (int)$this->pdo->lastInsertId();
        }

        throw new LogicException('Unknown PostgreSQL connection property: ' . $name);
    }
}

final class PgStatement
{
    private PgConnection $connection;
    private PDOStatement $statement;
    private array $bindings = [];
    public int $affected_rows = 0;

    public function __construct(PgConnection $connection, PDOStatement $statement)
    {
        $this->connection = $connection;
        $this->statement = $statement;
    }

    public function bind_param(string $types, &...$values): bool
    {
        if (strlen($types) !== count($values)) {
            throw new InvalidArgumentException('Parameter type count does not match the number of values.');
        }

        $this->bindings = [];
        foreach ($values as $index => &$value) {
            $this->bindings[] = [&$value, $types[$index]];
        }
        unset($value);

        return true;
    }

    public function execute(?array $params = null): bool
    {
        if ($params !== null) {
            foreach (array_values($params) as $index => $value) {
                $this->statement->bindValue($index + 1, $value, $this->parameterType($value, 's'));
            }
        } else {
            foreach ($this->bindings as $index => &$binding) {
                $value = $binding[0];
                $this->statement->bindValue($index + 1, $value, $this->parameterType($value, $binding[1]));
            }
            unset($binding);
        }

        $result = $this->statement->execute();
        $this->affected_rows = $this->statement->rowCount();

        return $result;
    }

    public function get_result(): PgResult
    {
        return new PgResult($this->statement);
    }

    public function close(): bool
    {
        return $this->statement->closeCursor();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'insert_id') {
            return $this->connection->insert_id;
        }
        if ($name === 'error') {
            return '';
        }

        throw new LogicException('Unknown PostgreSQL statement property: ' . $name);
    }

    private function parameterType(mixed $value, string $type): int
    {
        if ($value === null) {
            return PDO::PARAM_NULL;
        }

        if ($type === 'i') {
            return PDO::PARAM_INT;
        }
        if ($type === 'b') {
            return PDO::PARAM_LOB;
        }

        return PDO::PARAM_STR;
    }
}

final class PgResult
{
    private array $rows;
    private int $cursor = 0;
    public int $num_rows;

    public function __construct(PDOStatement $statement)
    {
        $this->rows = $statement->columnCount() > 0
            ? $statement->fetchAll(PDO::FETCH_ASSOC)
            : [];
        $this->num_rows = count($this->rows);
    }

    public function fetch_assoc(): ?array
    {
        if (!array_key_exists($this->cursor, $this->rows)) {
            return null;
        }

        return $this->rows[$this->cursor++];
    }

    public function fetch_all(int $mode = PDO::FETCH_ASSOC): array
    {
        $rows = array_slice($this->rows, $this->cursor);
        $this->cursor = count($this->rows);

        return $rows;
    }
}
