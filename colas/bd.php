<?php
class DatabaseService {
    private $pdo;

    public function __construct() {
        try {
            $dsn = 'mysql:host=localhost;dbname=banco_db';
            $username = 'root';
            $password = '';
            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => true // Activar conexiones persistentes
            );
            
            $this->pdo = new PDO($dsn, $username, $password, $options);
            $this->pdo->exec("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
        } catch (PDOException $e) {
            throw new SoapFault("Server", "Nivel 3: Error - No se pudo conectar a la base de datos: " . $e->getMessage());
        }
    }

    // Verificar si una persona con un token ya está registrada
    public function checkIfPersonExists($token) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM clientes WHERE token = :token");
            $stmt->execute(['token' => $token]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new SoapFault("Server", "Nivel 3: Error - Esta persona ya existe: " . $e->getMessage());
        }
    }

    // Verificar si el carnet ya está registrado
    public function checkIfCarnetExists($numero_carnet) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM clientes WHERE numero_carnet = :numero_carnet");
            $stmt->execute(['numero_carnet' => $numero_carnet]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new SoapFault("Server", "Nivel 3: Error - Este carnet ya fue utilizado: " . $e->getMessage());
        }
    }

    // Verificar si el login ya está registrado
    public function checkIfLoginExists($login) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM clientes WHERE login = :login");
            $stmt->execute(['login' => $login]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new SoapFault("Server", "Nivel 3: Error - Este login ya fue utilizado: " . $e->getMessage());
        }
    }
    
    // Obtener información de la persona por token
    public function getPersonInfo($token) {
        try {
            $stmt = $this->pdo->prepare("SELECT nombre, apellido_paterno, apellido_materno, numero_carnet FROM clientes WHERE token = :token");
            $stmt->execute(['token' => $token]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new SoapFault("Server", "Nivel 3: Error - Problema al obtener la información de la persona: " . $e->getMessage());
        }
    }

    // Insertar nueva persona en la base de datos
    public function insertPerson($nombre, $apellido_paterno, $apellido_materno, $numero_carnet, $fecha_nacimiento, $sexo, $lugar_nacimiento, $estado_civil, $profesion, $domicilio, $login, $password, $token) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO clientes 
                (nombre, apellido_paterno, apellido_materno, numero_carnet, fecha_nacimiento, sexo, lugar_nacimiento, estado_civil, profesion, domicilio, login, password, token) 
                VALUES 
                (:nombre, :apellido_paterno, :apellido_materno, :numero_carnet, :fecha_nacimiento, :sexo, :lugar_nacimiento, :estado_civil, :profesion, :domicilio, :login, :password, :token)
            ");
            $result = $stmt->execute([
                'nombre' => $nombre,
                'apellido_paterno' => $apellido_paterno,
                'apellido_materno' => $apellido_materno,
                'numero_carnet' => $numero_carnet,
                'fecha_nacimiento' => $fecha_nacimiento,
                'sexo' => $sexo,
                'lugar_nacimiento' => $lugar_nacimiento,
                'estado_civil' => $estado_civil,
                'profesion' => $profesion,
                'domicilio' => $domicilio,
                'login' => $login,
                'password' => $password,
                'token' => $token
            ]);
            return $result;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { // Código para violaciones de integridad
                throw new SoapFault("Client", "Nivel 2: Error - Este registro ya existe en el sistema.");
            } else {
                throw new SoapFault("Server", "Nivel 3: Error - Problema al insertar la persona en la base de datos: " . $e->getMessage());
            }
        }
    }

    // Autenticar login y contraseña
    public function authenticateLogin($login, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT password FROM clientes WHERE login = :login");
            $stmt->execute(['login' => $login]);
            $storedPassword = $stmt->fetchColumn();

            if ($storedPassword && password_verify($password, $storedPassword)) {
                return true; // Autenticación exitosa
            } else {
                return false; // Login o contraseña incorrectos
            }
        } catch (PDOException $e) {
            throw new SoapFault("Server", "Nivel 3: Error - Problema al autenticar el login: " . $e->getMessage());
        }
    }

    // Verificar si ya existe una cuenta del mismo tipo para este cliente
    public function checkIfAccountExists($login, $tipo_cuenta) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM cuentas WHERE login = :login AND tipo_cuenta = :tipo_cuenta");
            $stmt->execute(['login' => $login, 'tipo_cuenta' => $tipo_cuenta]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new SoapFault("Server", "Nivel 3: Error - Problema al verificar la existencia de la cuenta: " . $e->getMessage());
        }
    }

    // Insertar nueva cuenta en la base de datos
    public function insertAccount($login, $tipo_cuenta, $token) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO cuentas (login, tipo_cuenta, token, saldo, creado_en)
                VALUES (:login, :tipo_cuenta, :token, 0, NOW())
            ");
            $result = $stmt->execute([
                'login' => $login,
                'tipo_cuenta' => $tipo_cuenta,
                'token' => $token
            ]);
            return $result;
        } catch (PDOException $e) {
            throw new SoapFault("Server", "Nivel 3: Error - Problema al insertar la cuenta: " . $e->getMessage());
        }
    }

    // Obtener las cuentas del cliente
    public function getCuentasCliente($login) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, tipo_cuenta, saldo FROM cuentas WHERE login = :login");
            $stmt->execute(['login' => $login]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new SoapFault("Server", "Nivel 3: Error - Problema al obtener las cuentas del cliente: " . $e->getMessage());
        }
    }

    // Insertar una transacción de depósito
    public function depositar($cuenta_id, $monto, $token) {
        try {
            // Iniciar la transacción
            $this->pdo->beginTransaction();
    
            // Verificar si la cuenta existe y obtener el saldo actual (bloqueando la fila con FOR UPDATE)
            $stmt = $this->pdo->prepare("SELECT saldo FROM cuentas WHERE id = :cuenta_id FOR UPDATE");
            $stmt->execute(['cuenta_id' => $cuenta_id]);
            $saldo = $stmt->fetchColumn();
    
            if ($saldo === false) {
                throw new SoapFault("Client", "Nivel 2: Error - Cuenta no encontrada.");
            }
    
            // Insertar la transacción de depósito
            $stmt = $this->pdo->prepare("
                INSERT INTO transacciones (cuenta_id, tipo_transaccion, monto, token) 
                VALUES (:cuenta_id, 'deposito', :monto, :token)
            ");
            $stmt->execute(['cuenta_id' => $cuenta_id, 'monto' => $monto, 'token' => $token]);
    
            // Actualizar el saldo de la cuenta
            $stmt = $this->pdo->prepare("UPDATE cuentas SET saldo = saldo + :monto, actualizado_en = NOW() WHERE id = :cuenta_id");
            $stmt->execute(['monto' => $monto, 'cuenta_id' => $cuenta_id]);
    
            // Confirmar la transacción
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            // En caso de error, revertir la transacción
            $this->pdo->rollBack();
            if ($e->getCode() == '23000') {
                throw new SoapFault("Client", "Nivel 2: Error - Esta transacción ya existe.");
            } else {
                throw new SoapFault("Server", "Nivel 3: Error - Problema al realizar el depósito: " . $e->getMessage());
            }
        }
    }

    // Insertar una transacción de retiro
    public function retirar($cuenta_id, $monto, $token) {
        try {
            // Iniciar la transacción
            $this->pdo->beginTransaction();
    
            // Verificar si la cuenta tiene saldo suficiente
            $stmt = $this->pdo->prepare("SELECT saldo FROM cuentas WHERE id = :cuenta_id FOR UPDATE");
            $stmt->execute(['cuenta_id' => $cuenta_id]);
            $saldo = $stmt->fetchColumn();
    
            if ($saldo === false) {
                throw new SoapFault("Client", "Nivel 2: Error - Cuenta no encontrada.");
            }
    
            // Validar si hay suficiente saldo
            if ($saldo < $monto) {
                throw new SoapFault("Client", "Nivel 2: Error - Fondos insuficientes. Saldo disponible: $saldo.");
            }
    
            // Proceder con el retiro
            $stmt = $this->pdo->prepare("
                INSERT INTO transacciones (cuenta_id, tipo_transaccion, monto, token) 
                VALUES (:cuenta_id, 'retiro', :monto, :token)
            ");
            $stmt->execute(['cuenta_id' => $cuenta_id, 'monto' => $monto, 'token' => $token]);
    
            // Actualizar el saldo de la cuenta
            $stmt = $this->pdo->prepare("UPDATE cuentas SET saldo = saldo - :monto, actualizado_en = NOW() WHERE id = :cuenta_id");
            $stmt->execute(['monto' => $monto, 'cuenta_id' => $cuenta_id]);
    
            // Confirmar la transacción
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            // En caso de error, revertir la transacción
            $this->pdo->rollBack();
            if ($e->getCode() == '23000') {
                throw new SoapFault("Client", "Nivel 2: Error - Esta transacción ya existe.");
            } else {
                throw new SoapFault("Server", "Nivel 3: Error - Problema al realizar el retiro: " . $e->getMessage());
            }
        }
    }
    
////////////// DEPOSITAR, RETIRAR (evitar numeros negativos) ////////////////////////

    // Insertar una transacción de depósito
    public function depositarSaldo($cuenta_id, $monto, $token) {
        try {
            // Iniciar la transacción
            $this->pdo->beginTransaction();

            // Verificar si la cuenta existe y obtener el saldo actual (bloqueando la fila con FOR UPDATE)
            $stmt = $this->pdo->prepare("SELECT saldo FROM cuentas WHERE id = :cuenta_id FOR UPDATE");
            $stmt->execute(['cuenta_id' => $cuenta_id]);
            $saldo = $stmt->fetchColumn();

            if ($saldo === false) {
                throw new Exception("Cuenta no encontrada.");
            }

            // Insertar la transacción de depósito
            $stmt = $this->pdo->prepare("
                INSERT INTO transacciones (cuenta_id, tipo_transaccion, monto, token) 
                VALUES (:cuenta_id, 'deposito', :monto, :token)
            ");
            $stmt->execute(['cuenta_id' => $cuenta_id, 'monto' => $monto, 'token' => $token]);

            // Actualizar el saldo de la cuenta
            $stmt = $this->pdo->prepare("UPDATE cuentas SET saldo = saldo + :monto, actualizado_en = NOW() WHERE id = :cuenta_id");
            $stmt->execute(['monto' => $monto, 'cuenta_id' => $cuenta_id]);

            // Confirmar la transacción
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            // En caso de error, revertir la transacción
            $this->pdo->rollBack();
            if ($e->getCode() == '23000') {
                throw new Exception("Error - Esta transacción ya existe.");
            } else {
                throw new Exception("Problema al realizar el depósito: " . $e->getMessage());
            }
        }
    }

    // Insertar una transacción de retiro
    public function retirarSaldo($cuenta_id, $monto, $token) {
        try {
            // Iniciar la transacción
            $this->pdo->beginTransaction();

            // Verificar si la cuenta tiene saldo suficiente
            $stmt = $this->pdo->prepare("SELECT saldo FROM cuentas WHERE id = :cuenta_id FOR UPDATE");
            $stmt->execute(['cuenta_id' => $cuenta_id]);
            $saldo = $stmt->fetchColumn();

            if ($saldo === false) {
                throw new Exception("Cuenta no encontrada.");
            }

            // Validar si hay suficiente saldo
            if ($saldo < $monto) {
                throw new Exception("Fondos insuficientes. Saldo disponible: $saldo.");
            }

            // Proceder con el retiro
            $stmt = $this->pdo->prepare("
                INSERT INTO transacciones (cuenta_id, tipo_transaccion, monto, token) 
                VALUES (:cuenta_id, 'retiro', :monto, :token)
            ");
            $stmt->execute(['cuenta_id' => $cuenta_id, 'monto' => $monto, 'token' => $token]);

            // Actualizar el saldo de la cuenta
            $stmt = $this->pdo->prepare("UPDATE cuentas SET saldo = saldo - :monto, actualizado_en = NOW() WHERE id = :cuenta_id");
            $stmt->execute(['monto' => $monto, 'cuenta_id' => $cuenta_id]);

            // Confirmar la transacción
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            // En caso de error, revertir la transacción
            $this->pdo->rollBack();
            if ($e->getCode() == '23000') {
                throw new Exception("Error - Esta transacción ya existe.");
            } else {
                throw new Exception("Problema al realizar el retiro: " . $e->getMessage());
            }
        }
    }

//////////////////////////////////////////////////////////////////////////




// Obtener todas las cuentas, la cuenta con más y menos saldo
public function getCuentasInfo() {
    try {
        // Obtener todas las cuentas
        $stmt = $this->pdo->prepare("SELECT id, saldo FROM cuentas");
        $stmt->execute();
        $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener la cuenta con más saldo
        $stmt = $this->pdo->prepare("SELECT id, saldo FROM cuentas ORDER BY saldo DESC LIMIT 1");
        $stmt->execute();
        $maxCuenta = $stmt->fetch(PDO::FETCH_ASSOC);

        // Obtener la cuenta con menos saldo
        $stmt = $this->pdo->prepare("SELECT id, saldo FROM cuentas ORDER BY saldo ASC LIMIT 1");
        $stmt->execute();
        $minCuenta = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'cuentas' => $cuentas,
            'maxCuenta' => $maxCuenta, // Contiene el id y el saldo de la cuenta con más saldo
            'minCuenta' => $minCuenta  // Contiene el id y el saldo de la cuenta con menos saldo
        ];
    } catch (PDOException $e) {
        throw new SoapFault("Server", "Nivel 3: Error - No se pudo obtener la información de las cuentas.");
    }
}


}

// Configuración del servidor SOAP en PC3
$server = new SoapServer(null, ['uri' => "urn:DatabaseService"]);
$server->setClass('DatabaseService');
$server->handle();
?>