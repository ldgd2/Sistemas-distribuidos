from flask import Flask, render_template, request, jsonify
import requests
import hashlib
import logging

app = Flask(__name__)

# IP del servidor Flask que maneja las solicitudes REST (ubicado en el servidor)
SERVER_HOST = '192.168.100.141:5000'  # IP del servidor (DB y API)

# Configuración básica de logging
logging.basicConfig(filename='client.log', level=logging.ERROR, format='%(asctime)s %(levelname)s: %(message)s')

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/submit', methods=['POST'])
def submit():
    try:
        data = request.json

        # Generar un token único para la transacción 
        token_input = (
            data['name'] + 
            data['paternal_surname'] + 
            data['maternal_surname'] + 
            data['id_number'] + 
            data['birth_date'] + 
            data['gender'] + 
            data['birth_place'] + 
            data['marital_status'] + 
            data['profession'] + 
            data['address']
        )
        token = hashlib.md5(token_input.encode()).hexdigest()
        data['token'] = token

        # Enviar los datos al servidor Flask
        response = requests.post(f'http://{SERVER_HOST}/register', json=data)
        response.raise_for_status()  # Lanza una excepción para códigos de estado HTTP 

        response_data = response.json()
        if response_data.get('error'):
            return jsonify({'success': False, 'message': response_data['message'], 'error_level': 'db'})
        else:
            return jsonify({'success': True, 'message': 'Persona registrada exitosamente.'})

    except requests.exceptions.ConnectionError:
        logging.error("Error de conexión con el servidor.")
        return jsonify({'success': False, 'message': 'Error de conexión, no se pudo realizar la transacción.', 'error_level': 'connection'})
    except requests.exceptions.Timeout:
        logging.error("Error de tiempo de espera agotado al conectar con el servidor.")
        return jsonify({'success': False, 'message': 'La solicitud excedió el tiempo de espera, intente nuevamente.', 'error_level': 'connection'})
    except requests.exceptions.RequestException as e:
        logging.error(f"Error general en la solicitud: {str(e)}")
        return jsonify({'success': False, 'message': f'Error en el servidor: {str(e)}', 'error_level': 'server'})
    except Exception as e:
        logging.error(f"Error desconocido: {str(e)}")
        return jsonify({'success': False, 'message': f'Ocurrió un error inesperado: {str(e)}', 'error_level': 'server'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8000, debug=True)
