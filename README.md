### Introducción
> Test API Multimedia Contents

### Instalacion
``` 
$ composer install --no-dev --optimize-autoloader; yarn install; yarn encore prod;
```

### Docker
``` 
$ docker compose up -d
```

### Documentaciones
* [Swagger](http://localhost/api/doc)
* [Json Import](http://localhost/api/doc.json)
* [PHPMyAdmin](http://localhost:8080) (root:password)

### Sugerencias
* Separar el CRUD de Medias del contexto Contenidos
* Implementar Paginaciones a los listados de Contenidos
* Implementar Rate Total para los Contenidos
* Implementar DTOs y Mappers para los Metodos 

### Desarrollador:
* Ing. Gilberto Carrillo <glavrjk@gmail.com>