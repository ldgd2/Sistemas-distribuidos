import MySQLdb
import logging

class DatabaseService:
    def __init__(self, host='192.168.100.141', user='root', password='Lider@@', db='person_db', port=3306):
        """
        Conexión a la base de datos MySQL/MariaDB.
        """
        try:
            self.conn = MySQLdb.connect(host=host, user=user, passwd=password, db=db, port=port)
            self.cursor = self.conn.cursor(MySQLdb.cursors.DictCursor)
        except MySQLdb.Error as e:
            logging.error(f"Error al conectar con la base de datos: {e}")
            self.cursor = None
            self.conn = None
            raise

    def check_if_person_exists(self, numero_carnet):
        """ Verifica si una persona ya está registrada en la base de datos. """
        if self.cursor:
            try:
                query = "SELECT COUNT(*) as count FROM personas WHERE numero_carnet = %s"
                self.cursor.execute(query, [numero_carnet])
                result = self.cursor.fetchone()
                return result['count'] > 0
            except MySQLdb.Error as e:
                logging.error(f"Error al verificar existencia: {e}")
                raise
        else:
            raise Exception("No se pudo verificar la existencia porque la conexión a la base de datos no se estableció.")

    def get_person_info(self, numero_carnet):
        """ Obtiene la información de una persona registrada en la base de datos. """
        if self.cursor:
            try:
                query = "SELECT * FROM personas WHERE numero_carnet = %s"
                self.cursor.execute(query, [numero_carnet])
                return self.cursor.fetchone()
            except MySQLdb.Error as e:
                logging.error(f"Error al obtener la información de la persona: {e}")
                raise
        else:
            raise Exception("No se pudo obtener la información de la persona porque la conexión a la base de datos no se estableció.")

    def insert_person(self, data):
        """ Inserta una nueva persona en la base de datos. """
        if self.cursor:
            try:
                query = """
                INSERT INTO personas 
                (nombre, apellido_paterno, apellido_materno, numero_carnet, fecha_nacimiento, sexo, lugar_nacimiento, estado_civil, profesion, domicilio, token) 
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                """
                self.cursor.execute(query, (
                    data['name'], data['paternal_surname'], data['maternal_surname'],
                    data['id_number'], data['birth_date'], data['gender'],
                    data['birth_place'], data['marital_status'], data['profession'],
                    data['address'], data['token']
                ))
                self.conn.commit()
                return True
            except MySQLdb.Error as e:
                logging.error(f"Error al insertar persona: {e}")
                self.conn.rollback()
                raise
        else:
            raise Exception("No se pudo insertar la persona porque la conexión a la base de datos no se estableció.")

    def __del__(self):
        """ Cierra la conexión a la base de datos al destruir el objeto. """
        if hasattr(self, 'cursor') and self.cursor:
            try:
                self.cursor.close()
            except MySQLdb.Error as e:
                logging.error(f"Error al cerrar el cursor: {e}")

        if hasattr(self, 'conn') and self.conn:
            try:
                self.conn.close()
            except MySQLdb.Error as e:
                logging.error(f"Error al cerrar la conexión: {e}")
