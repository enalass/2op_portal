# Requisitos de servidor para subida grande (Fase 5)

## PHP (php.ini)

- upload_max_filesize = 1024M
- post_max_size = 1600M
- max_file_uploads = 2000
- max_execution_time = 8000
- max_input_time = 8000
- memory_limit = 1024M (recomendado)

## Nginx (si aplica)

- client_max_body_size 1600M;
- fastcgi_read_timeout 1800;

## Apache (si aplica)

- LimitRequestBody 1677721600
- Timeout 1800

## Aplicación

- Límite por archivo: 1GB
- Límite por envío: 1.5GB aprox
- Límite de archivos por envío: 1500
- Extensiones permitidas: .dcm, .dicom, .zip
