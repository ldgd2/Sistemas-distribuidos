<?php


class Serializado {

// Serializar un objeto a JSON
public function serializar($data) {
    return json_encode($data, JSON_PRETTY_PRINT);
}

// Deserializar un JSON a un objeto PHP
public function deserializar($json) {
    return json_decode($json, true);
}

// Guardar un objeto serializado en un archivo
public function guardarArchivo($data, $filename) {
    $json = $this->serializar($data);
    file_put_contents($filename, $json);
}

// Cargar un objeto desde un archivo
public function cargarArchivo($filename) {
    if (!file_exists($filename)) {
        return null;
    }

    $json = file_get_contents($filename);
    return $this->deserializar($json);
}
}
?>
