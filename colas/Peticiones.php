<?php


class Peticiones {
    private $archivoCola = 'peticiones.json'; // Archivo donde se guardan las peticiones
    private $archivoHistorial = 'historial.json'; // Archivo donde se guardan las peticiones realizadas

    // Método para leer las peticiones pendientes de la cola
    public function leerCola() {
        if (!file_exists($this->archivoCola)) {
            return []; // Si no existe el archivo, retorna una cola vacía
        }

        $contenido = file_get_contents($this->archivoCola);
        $cola = json_decode($contenido, true);
        return isset($cola['peticiones']) ? $cola['peticiones'] : [];
    }

    // Método para agregar una nueva petición a la cola
    public function agregarPeticion($tipo, $data) {
        $peticion = [
            'id' => uniqid(),  // Generar un ID único para la petición
            'tipo' => $tipo,  // Tipo de servicio (depositar, retirar, etc.)
            'data' => $data,  // Datos relevantes para la operación
            'estado' => 'Pendiente',  // Estado inicial
            'intentos' => 0  // Intentos de ejecución
        ];

        $contenido = file_exists($this->archivoCola) ? file_get_contents($this->archivoCola) : '{"peticiones":[]}';
        $cola = json_decode($contenido, true);
        $cola['peticiones'][] = $peticion;

        file_put_contents($this->archivoCola, json_encode($cola, JSON_PRETTY_PRINT));
    }

    // Método para marcar una petición como realizada y moverla al historial
    public function marcarComoRealizada($peticion) {
        $contenido = file_get_contents($this->archivoCola);
        $cola = json_decode($contenido, true);

        // Eliminar la petición de la cola
        $cola['peticiones'] = array_filter($cola['peticiones'], function($p) use ($peticion) {
            return $p['id'] !== $peticion['id'];
        });

        // Guardar la cola actualizada
        file_put_contents($this->archivoCola, json_encode($cola, JSON_PRETTY_PRINT));

        // Mover la petición al historial
        $historial = file_exists($this->archivoHistorial) ? file_get_contents($this->archivoHistorial) : '{"historial":[]}';
        $historialData = json_decode($historial, true);
        $peticion['estado'] = 'Realizado';
        $historialData['historial'][] = $peticion;

        file_put_contents($this->archivoHistorial, json_encode($historialData, JSON_PRETTY_PRINT));
    }

    // Método para marcar una petición con error
    public function marcarError($peticion) {
        $contenido = file_get_contents($this->archivoCola);
        $cola = json_decode($contenido, true);

        foreach ($cola['peticiones'] as &$p) {
            if ($p['id'] === $peticion['id']) {
                $p['estado'] = 'Erronea';
                $p['intentos'] += 1;
            }
        }

        file_put_contents($this->archivoCola, json_encode($cola, JSON_PRETTY_PRINT));
    }
}
?>