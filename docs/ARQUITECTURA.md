# SIGEDI – Arquitectura y modelo de datos (Sprint 1)

Evidencia de HT01 (arquitectura MVC) y HT02 (modelo relacional).

## 1. Arquitectura en capas (MVC sobre Laravel)

```mermaid
flowchart LR
    U[Navegador del usuario] --> R[routes/web.php]
    R --> M1[Middleware: auth, EnsureUserIsActive, RoleMiddleware]
    M1 --> C[Controladores<br/>LoginController, DashboardController, UserController]
    C --> FR[Form Requests<br/>validación]
    C --> MO[Modelos Eloquent<br/>User, Role, Ubicacion, Dispositivo, Incidencia, Bitacora]
    MO --> DB[(MySQL / MariaDB)]
    C --> V[Vistas Blade + Bootstrap]
    V --> U
```

| Capa | Ubicación en el proyecto | Responsabilidad |
|---|---|---|
| Rutas | `routes/web.php` | Define URL, verbo HTTP, controlador y middleware |
| Middleware | `app/Http/Middleware` | Autenticación, usuario activo, control de acceso por rol |
| Controlador | `app/Http/Controllers` | Orquesta la petición; no contiene reglas de datos |
| Validación | `app/Http/Requests` | Reglas y mensajes en español |
| Modelo | `app/Models` | Acceso a datos y relaciones |
| Vista | `resources/views` | Presentación (Blade + Bootstrap 5, responsive) |
| Base de datos | `database/migrations`, `database/seeders` | Estructura versionada y datos iniciales |
| Pruebas | `tests/Feature` | Verificación automática de criterios de aceptación |

## 2. Modelo entidad-relación

```mermaid
erDiagram
    ROLES ||--o{ USERS : "tiene"
    USERS ||--o{ DISPOSITIVOS : "es responsable de"
    UBICACIONES ||--o{ DISPOSITIVOS : "alberga"
    DISPOSITIVOS ||--o{ INCIDENCIAS : "presenta"
    USERS ||--o{ INCIDENCIAS : "solicita"
    USERS |o--o{ INCIDENCIAS : "atiende (técnico)"
    USERS |o--o{ BITACORAS : "realiza"
    DISPOSITIVOS |o--o{ BITACORAS : "registra"
    INCIDENCIAS |o--o{ BITACORAS : "registra"

    ROLES { bigint id PK
            string nombre UK
            string descripcion }
    USERS { bigint id PK
            bigint role_id FK
            string name
            string email UK
            string whatsapp UK
            boolean activo
            string password }
    UBICACIONES { bigint id PK
            string nombre UK
            boolean activo }
    DISPOSITIVOS { bigint id PK
            string codigo_patrimonial UK
            string tipo
            string marca
            string modelo
            string serie
            text caracteristicas
            string estado
            bigint ubicacion_id FK
            bigint responsable_id FK }
    INCIDENCIAS { bigint id PK
            string codigo UK
            bigint dispositivo_id FK
            bigint solicitante_id FK
            bigint tecnico_id FK
            string categoria
            text descripcion
            string impacto
            string urgencia
            string prioridad
            string estado
            string canal
            text solucion
            timestamp fecha_asignacion
            timestamp fecha_cierre }
    BITACORAS { bigint id PK
            bigint user_id FK
            bigint dispositivo_id FK
            bigint incidencia_id FK
            string tipo
            string accion
            text descripcion }
```

## 3. Decisiones de diseño

- **Roles:** tabla propia (`roles`) con tres valores: administrador, técnico y usuario (RF01, RF02).
- **Usuarios inactivos:** no se eliminan; se desactivan para conservar el historial de incidencias y bitácora.
- **Seguridad (RNF01):** contraseñas con bcrypt (cast `hashed`), protección CSRF de Laravel, regeneración de sesión al ingresar, límite de 5 intentos fallidos por minuto y control de acceso por rol (error 403).
- **Eliminaciones:** las claves foráneas de bitácora y responsable usan `nullOnDelete` para no perder trazabilidad; dispositivo e incidencia usan restricción.
- **Claves de unicidad:** `codigo_patrimonial` (HU03), `codigo` de incidencia (HU09), `email` y `whatsapp` de usuario.
