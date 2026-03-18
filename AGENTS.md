# AGENTS.md

## Contexto del proyecto

Este repositorio es una aplicación en **CodeIgniter 3 (PHP)** con arquitectura MVC clásica.

El agente se utiliza para:
- generar fragmentos de código
- corregir bugs
- añadir funcionalidades pequeñas o medianas
- mejorar código existente sin romper compatibilidad

⚠️ Este proyecto **NO está en migración**.  
NO proponer cambios a CodeIgniter 4 ni a otros frameworks.

---

## Stack técnico

- PHP (CodeIgniter 3)
- MySQL / MariaDB
- jQuery (principal para AJAX)
- Bootstrap (UI)
- HTML + CSS + JS vanilla cuando aplique

---

## Librería DICOM (DCMTK 3.7.0) ⚠️ IMPORTANTE

Este proyecto utiliza la librería **DCMTK 3.7.0** como herramienta DICOM local.

### Reglas obligatorias

- SIEMPRE usar **DCMTK 3.7.0** para cualquier operación DICOM
- NO proponer librerías alternativas (pydicom, dcm4che, etc.)
- NO usar servicios externos o APIs cloud para DICOM
- NO reemplazar DCMTK bajo ninguna circunstancia
- asumir que DCMTK 3.7.0 está instalado en `/opt/dcmtk-3.7.0/bin`
- priorizar siempre el uso de rutas absolutas en comandos PHP
- no asumir que Apache hereda variables PATH del shell

---

### Forma de integración esperada

DCMTK se usa típicamente mediante comandos CLI como:

- `dcmdump` → lectura de metadatos
- `dcm2json` → conversión a JSON
- `dcmj2pnm` → extracción de imágenes
- `storescu` / `storescp` → envío/recepción DICOM

Desde PHP (CodeIgniter), se debe usar:

- `exec()`
- `shell_exec()`

o mecanismos equivalentes ya existentes en el proyecto.

---

### Buenas prácticas con DCMTK

- Encapsular llamadas a DCMTK en:
  - libraries (`application/libraries`)
  - helpers
  - modelos (si ya existe ese patrón)

- NO ejecutar comandos directamente en controllers si se puede evitar

- Sanitizar rutas y parámetros antes de ejecutar comandos

- Controlar errores de ejecución:
  - archivo no encontrado
  - comando falla
  - salida vacía
  - permisos

---

### Casos de uso típicos

El agente debe soportar:

- lectura de metadatos DICOM
- extracción de tags (PatientName, StudyInstanceUID, etc.)
- conversión a JSON
- generación de previews/imágenes
- procesamiento de archivos `.dcm`
- validación de archivos DICOM
- integración con base de datos

---

### Supuestos cuando falte contexto

Si no se especifica:

- asumir que DCMTK está disponible en PATH del sistema
- asumir rutas tipo `/var/dicom/` o configuradas en el proyecto
- asumir uso de `dcmdump` para lectura básica

SIEMPRE indicar estos supuestos en la respuesta.

---

## Estructura del proyecto

application/
  controllers/
  models/
  views/
  config/
  helpers/
  libraries/

assets/
  js/
  css/
  img/

---

## Principios clave (OBLIGATORIOS)

### 1. Compatibilidad total con CodeIgniter 3
Usar siempre:
- `CI_Controller`
- `CI_Model`
- `$this->load->model()`
- `$this->load->view()`
- `$this->input->post() / get()`
- `$this->db` (Query Builder)

---

### 2. Cambios mínimos
- NO reescribir archivos completos si no se pide
- NO refactorizar grandes bloques
- NO cambiar nombres públicos existentes

---

### 3. Respetar MVC
- Controllers → flujo y lógica HTTP
- Models → acceso a datos
- Views → presentación (mínima lógica)

---

### 4. No inventar contexto
Si falta información:
- usar supuestos conservadores
- declararlos en "Supuestos"
- NO inventar estructuras críticas sin avisar

---

### 5. Seguridad
Siempre que aplique:
- validar inputs
- sanitizar parámetros para exec/shell_exec
- usar Query Builder (evitar SQL crudo inseguro)
- evitar XSS (escapar salida si procede)
- contemplar CSRF si el proyecto lo usa

---

### 6. Consistencia con el proyecto
- Seguir naming existente
- Seguir estilo de queries existente
- Reutilizar helpers/libraries si existen
- No duplicar lógica innecesariamente

---

## Forma de responder (FORMATO OBLIGATORIO)

Siempre estructurar así:

### Objetivo
Qué se va a hacer (máx 3–4 líneas)

### Supuestos
- Supuesto 1
- Supuesto 2

### Archivos afectados
- ruta/archivo1.php
- ruta/archivo2.php

### Código

#### Archivo: ruta/archivo1.php
```php
// código listo para copiar