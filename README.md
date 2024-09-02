# Sistemas-distribuidos

# Implementación de un Sistema de Registro con Método REST y Manejo de Tokens

Este proyecto implementa un sistema de registro distribuido utilizando un método REST, generando tokens únicos para cada transacción y manejando errores a tres niveles (conexión, servidor, base de datos). A continuación, se describe la instalación de dependencias, la arquitectura del proyecto y el funcionamiento de cada componente.

## Instalación de Dependencias

### Requisitos Previos
1. **Python**: Asegúrate de tener Python instalado en tu sistema.
2. **XAMPP**: Necesario para ejecutar MySQL/MariaDB. Asegúrate de que el servicio MySQL esté corriendo.

### Instalación de Dependencias Python
Abre la línea de comandos (CMD) y ejecuta los siguientes comandos para instalar las bibliotecas necesarias:

pip install flask

pip install flask-cors

pip install mysqlclient

pip install requests

pip install loguru

## Arquitectura del Proyecto
El proyecto está dividido en tres componentes principales:

# 1. server.py
Este archivo es el servidor principal que maneja las solicitudes REST provenientes del cliente.

Ruta /register:
Método POST: Recibe los datos JSON enviados por el cliente.
Valida que todos los campos requeridos estén presentes en la solicitud.
Utiliza el módulo db.py para interactuar con la base de datos.
Si la persona ya existe en la base de datos, devuelve un mensaje informando que el usuario ya está registrado.
Si es un nuevo usuario, lo inserta en la base de datos y retorna un mensaje de éxito.
Se implementa un manejo de errores a tres niveles: conexión, servidor y base de datos, con mensajes adecuados en cada caso.
# 2. client.py
Este archivo actúa como intermediario entre la interfaz HTML (el formulario que llena el usuario) y el servidor REST.

Generación de Tokens:
Un token único es generado utilizando los datos del formulario mediante un hash MD5. Esto asegura que cada transacción sea única y evita duplicados.
Envío de Datos:
Los datos del formulario y el token generado se envían al servidor mediante una solicitud POST a la ruta /register.
Maneja los errores de conexión y muestra mensajes apropiados al usuario dependiendo del tipo de error.
Modos de Ejecución:
Pc1, Pc2, Pc3: Si se implementa en diferentes PCs, se debe cambiar la IP del servidor en SERVER_HOST para que apunte a la IP del servidor central en la red.
# 3. db.py
Este módulo maneja la interacción directa con la base de datos MySQL/MariaDB:

# Conexión a la Base de Datos:
Utiliza MySQLdb para conectarse a la base de datos, usando las credenciales configuradas.
Métodos Principales:
check_if_person_exists: Verifica si una persona con el mismo número de carnet ya está registrada.
get_person_info: Recupera la información de una persona si ya existe en la base de datos.
insert_person: Inserta un nuevo registro de persona en la base de datos.
Manejo de Errores:
Cualquier error en la base de datos se captura y registra en un archivo de log para facilitar la solución de problemas.
Manejo de Tokens
Los tokens son generados en el cliente utilizando los datos del formulario. Se asegura de que cada transacción de registro tenga un identificador único (token), lo cual evita la inserción de registros duplicados en la base de datos.

## Ejecución del Proyecto

# Server 
Debe ejecutarse en el servidor central donde se realizará la gestión de las solicitudes. Se ejecuta con:
python server.py

# Client
Se puede ejecutar en cualquier máquina de la red que desee realizar registros. Debe configurarse SERVER_HOST con la IP del servidor donde se ejecuta server.py. Se ejecuta con:

python client.py


# Base de Datos
Ejecuta MySQL/MariaDB mediante XAMPP. Asegúrate de que el servicio MySQL esté corriendo y que la base de datos person_db esté configurada con las tablas necesarias.

## Creación de la Base de Datos
Ejecuta las siguientes sentencias SQL para crear la base de datos y la tabla:

CREATE DATABASE person_db;

USE person_db;

CREATE TABLE personas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    apellido_paterno VARCHAR(255) NOT NULL,
    apellido_materno VARCHAR(255) NOT NULL,
    numero_carnet VARCHAR(255) NOT NULL UNIQUE,
    fecha_nacimiento DATE NOT NULL,
    sexo CHAR(1) NOT NULL,
    lugar_nacimiento VARCHAR(255) NOT NULL,
    estado_civil CHAR(1) NOT NULL,
    profesion VARCHAR(255) NOT NULL,
    domicilio VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


