from flask import Flask, request, jsonify
from db import DatabaseService
import logging
from flask_cors import CORS

app = Flask(__name__)
CORS(app)  # Habilitar CORS

# Configuración básica de logging
logging.basicConfig(filename='server.log', level=logging.ERROR, format='%(asctime)s %(levelname)s: %(message)s')

@app.route('/register', methods=['POST'])
def register_person():
    try:
        data = request.get_json()

        # Validación de datos en el servidor
        required_fields = ['name', 'paternal_surname', 'maternal_surname', 'id_number', 'birth_date', 'gender', 'birth_place', 'marital_status', 'profession', 'address', 'token']
        for field in required_fields:
            if field not in data:
                return jsonify({'error': True, 'message': f"'{field}' es obligatorio."}), 400

        db_service = DatabaseService(host='192.168.100.141', user='root', password='Lider@@', db='person_db')

        # Verificar si la persona ya existe
        if db_service.check_if_person_exists(data['id_number']):
            person_info = db_service.get_person_info(data['id_number'])
            if person_info['token'] == data['token']:
                return jsonify({'error': True, 'message': 'Hubo un problema al registrar, intente nuevamente.'}), 409
            
            return jsonify({
                'error': True,
                'message': 'La persona ya está registrada.',
                'person_info': {
                    'Nombre Completo': f"{person_info['nombre']} {person_info['apellido_paterno']} {person_info['apellido_materno']}",
                    'Número de Carnet': person_info['numero_carnet'],
                    'Profesión': person_info['profesion'],
                    'Domicilio': person_info['domicilio']
                }
            }), 200

        # Insertar nueva persona
        db_service.insert_person(data)
        return jsonify({'success': True, 'message': f"Registro exitoso de: {data['name']} {data['paternal_surname']} {data['maternal_surname']} - {data['id_number']}"}), 201

    except Exception as e:
        logging.error(f"Error en el registro: {str(e)}")
        return jsonify({'error': True, 'message': 'Hubo un problema con el servidor, intente más tarde.'}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
