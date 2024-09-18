import json
import threading
import time

# Función que simula la ejecución de una petición
def procesar_peticion(peticion, indice):
    print(f"Hilo {threading.current_thread().name} procesando petición {indice}: {peticion['tipo']}")
    time.sleep(2)  # Simulando tiempo de procesamiento
    peticion['estado'] = 'Realizado'
    return peticion

# Clase que maneja los hilos
class GestorHilos:
    def __init__(self, archivo_json):
        self.archivo_json = archivo_json

    # Método para leer las peticiones del archivo JSON
    def leer_peticiones(self):
        with open(self.archivo_json, 'r') as archivo:
            return json.load(archivo)

    # Método para escribir las peticiones actualizadas al archivo JSON
    def actualizar_peticiones(self, peticiones):
        with open(self.archivo_json, 'w') as archivo:
            json.dump(peticiones, archivo, indent=4)

    # Método que procesa las peticiones en hilos
    def procesar_cola(self):
        peticiones = self.leer_peticiones()

        hilos = []
        for indice, peticion in enumerate(peticiones):
            if peticion['estado'] == 'Pendiente':
                hilo = threading.Thread(target=self.procesar_y_actualizar, args=(peticion, indice, peticiones))
                hilos.append(hilo)
                hilo.start()

        # Esperar a que todos los hilos terminen
        for hilo in hilos:
            hilo.join()

    # Método que procesa una petición y actualiza el archivo JSON
    def procesar_y_actualizar(self, peticion, indice, peticiones):
        peticiones[indice] = procesar_peticion(peticion, indice)
        self.actualizar_peticiones(peticiones)

# Ejecución del gestor de hilos
if __name__ == "__main__":
    gestor = GestorHilos('cola_peticiones.json')
    gestor.procesar_cola()
