<?php
class Conexion {
    private $con;

   function __construct($config) {
    $host = $config['servidor'];
    $usuario = $config['usuario'];
    $clave = $config['contrasena'];
    $base = $config['bd'];
    $tipo = $config['tipo'] ?? 'mysql';
    $port = $config['port'];   

    $dsn = "$tipo:host=$host;port=$port;dbname=$base;charset=utf8mb4";
    $this->con = new PDO($dsn, $usuario, $clave, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
}

    function query($sql, $param = []) {
        $stmt = $this->con->prepare($sql);
        $stmt->execute($param);
        return $stmt;
    }

    function select($tabla, $campos = "*") {
        return new Select($this->con, $tabla, $campos);
    }

    function insert($tabla) {
        return new Insert($this->con, $tabla);
    }

    function update($tabla) {
        return new Update($this->con, $tabla);
    }

    function delete($tabla) {
        return new Delete($this->con, $tabla);
    }

    function support_groupby() {
        $this->con->query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
    }
}

class Insert {
    private $con;
    private $tabla;
    private $campos = [];
    private $valores = [];

    public function __construct($con, $tabla) {
        $this->con = $con;
        $this->tabla = $tabla;
    }

    public function create($campo, $valor) {
        $this->campos[] = $campo;
        $this->valores[] = $valor;
        return $this;
    }

    public function getQuery() {
        if (empty($this->campos)) {
            throw new Exception("No se han especificado campos para insertar.");
        }

        $camposStr = implode(", ", $this->campos);
        $placeholders = implode(", ", array_map(fn($i) => ":param$i", array_keys($this->valores)));

        return "INSERT INTO {$this->tabla} ($camposStr) VALUES ($placeholders)";
    }

    public function execute() {
        $sql = $this->getQuery();
        $stmt = $this->con->prepare($sql);
        $params = [];
            foreach ($this->valores as $i => $valor) {
                $params[":param$i"] = $valor;
            }
        $stmt->execute($params);
        return $stmt;
    }
}

// ------------------------------------
// Clase SELECT
// ------------------------------------

class Select {
    private $con;
    private $tabla;
    private $sql;
    private $joins = "";
    private $where = [];
    private $groupby = "";
    private $having = "";
    private $orderby = "";
    private $limit = "";
    private $params = [];

    public function __construct($con, $tabla, $campos = "*") {
        $this->con = $con;
        $this->tabla = $tabla;
        $this->sql = "SELECT $campos FROM $tabla"; // Sin backticks para evitar errores con alias
    }

    public function join($tipo, $tablaJoin, $condicion) {
    $tipo = strtoupper($tipo);
        if (stripos($condicion, 'USING') !== false) {
            $this->joins .= " $tipo JOIN $tablaJoin $condicion";
        } else {
            $this->joins .= " $tipo JOIN $tablaJoin ON $condicion";
        }
        return $this;
    }


    public function where($campo, $operador, $valor) {
        $this->where[] = [$campo, $operador, $valor];
        return $this;
    }

    public function groupby($group) {
        $this->groupby = "GROUP BY $group";
        return $this;
    }

    public function having($condicion) {
        $this->having = "HAVING $condicion";
        return $this;
    }

    public function orderby($orden) {
        $this->orderby = "ORDER BY $orden";
        return $this;
    }

    public function limit($limite) {
        $this->limit = "LIMIT $limite";
        return $this;
    }

    private function getQuery() {
        $sql = $this->sql;

        if ($this->joins) {
            $sql .= "\n$this->joins";
        }

        // Limpiar parámetros antes de reconstruirlos
        $this->params = [];

        if (!empty($this->where)) {
            $condiciones = array_map(function ($cond) {
                return "{$cond[0]} {$cond[1]} ?";
            }, $this->where);

            $sql .= "\nWHERE " . implode(" AND ", $condiciones);

            foreach ($this->where as $cond) {
                $this->params[] = $cond[2];
            }
        }

        if ($this->groupby) $sql .= "\n$this->groupby";
        if ($this->having)  $sql .= "\n$this->having";
        if ($this->orderby) $sql .= "\n$this->orderby";
        if ($this->limit)   $sql .= "\n$this->limit";

        return $sql;
    }

    public function fetchAll() {
        $stmt = $this->con->prepare($this->getQuery());
        $stmt->execute($this->params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetch() {
        $stmt = $this->con->prepare($this->getQuery());
        $stmt->execute($this->params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ------------------------------------
// Clase UPDATE
// ------------------------------------

class Update {
    private $con;
    private $tabla;
    private $campos = [];
    private $where = "";
    private $params = [];

    function __construct($con, $tabla) {
        $this->con = $con;
        $this->tabla = $tabla;
    }

    function set($campo, $valor) {
        $this->campos[$campo] = $valor;
        return $this;
    }

    function where($campo, $operador, $valor = null) {
    $this->where = "WHERE `$campo` $operador" . ($valor !== null ? " ?" : "");
    if ($valor !== null) {
        $this->params[] = $valor;
    }
    return $this;
}


function where_and($campo, $operador, $valor = null) {
    $usaParametro = !in_array(strtoupper($operador), ['IS', 'IS NOT']);

    if (!$this->where) {
        $this->where = "WHERE `$campo` $operador" . ($usaParametro ? " ?" : "");
    } else {
        $this->where .= " AND `$campo` $operador" . ($usaParametro ? " ?" : "");
    }

    if ($usaParametro && $valor !== null) {
        $this->params[] = $valor;
    }

    return $this;
}


    
    function getQuery() {
    if (empty($this->campos)) {
        throw new Exception("Error: No hay campos para actualizar.");
    }

    $asignaciones = [];
    $setParams = []; // separa los params del SET
    foreach ($this->campos as $campo => $valor) {
        $asignaciones[] = "`$campo` = ?";
        $setParams[] = $valor;
    }

    $sql = "UPDATE `$this->tabla` SET " . implode(", ", $asignaciones);
    if ($this->where) {
        $sql .= " " . $this->where;
    }

    // Combinar: primero los del SET, luego los del WHERE
    $this->params = array_merge($setParams, $this->params);

    return $sql;
    }

function execute() {
    $query = $this->getQuery();
    echo "SQL: " . $query . "<br>";
    echo "Params: ";
    print_r($this->params);
    
    $stmt = $this->con->prepare($query);
    $stmt->execute($this->params);
    return $stmt;
}


}

// ------------------------------------
// Clase DELETE
// ------------------------------------

class Delete {
    private $con;
    private $tabla;
    private $where = "";
    private $params = [];

    function __construct($con, $tabla) {
        $this->con = $con;
        $this->tabla = $tabla;
    }

    function where($condicion, $param = []) {
    $this->where = "WHERE $condicion";

    if (!is_array($param)) {
        $param = [$param];
    }
    $this->params = array_merge($this->params, $param);
    return $this;
}


    function getQuery() {
        $sql = "DELETE FROM `$this->tabla`";
        if ($this->where) {
            $sql .= " " . $this->where;
        }
        return $sql;
    }

    function execute() {
        $stmt = $this->con->prepare($this->getQuery());
        $stmt->execute($this->params);
        return $stmt;
    }
}
