# 📦 Sistema de Gestión de Órdenes de Compra

Este proyecto es una aplicación para gestionar órdenes de compra de manera eficiente. Utiliza tecnologías como **XAMPP** para el servidor local y **Git** para el control de versiones.

---

## ⚙️ Requisitos Previos

Antes de comenzar, asegúrate de tener instalado lo siguiente:

- [XAMPP](https://www.apachefriends.org/index.html)
- [Git](https://git-scm.com/)

---

## 🚀 Instalación y Ejecución

### 1. Clona este repositorio

Abre tu terminal (CMD, PowerShell o la terminal de VSCode) y ejecuta:

```bash
git clone https://github.com/jhosfernan12/OrdenesCompra.git
cd OrdenesCompra
```
### 2. Configura tu entorno local
Abre XAMPP y asegúrate de iniciar Apache y MySQL.

Copia el proyecto dentro de la carpeta htdocs de XAMPP.
Crea una base de datos en phpMyAdmin e importa el archivo .sql si está disponible en el repositorio.
Configura el archivo de conexión a la base de datos (config.php o similar) con tus credenciales locales.

##🤝 Cómo Contribuir
###1. Realiza un fork del repositorio
Entra al repositorio original en GitHub.

Haz clic en Fork (arriba a la derecha) para crear una copia en tu cuenta.

### 2. Clona tu fork
```bash
git clone https://github.com/tu-usuario/OrdenesCompra.git
cd OrdenesCompra
```
### 3. Crea una nueva rama para trabajar
```bash
git checkout -b nombre-de-tu-rama
# Ejemplo:
git checkout -b arreglar-login
```
### 4. Realiza tus cambios
Haz tus modificaciones y guarda los archivos.
### 5. Agrega y haz commit de los cambios
```bash
git add .
git commit -m "Descripción clara de lo que hiciste"
# Ejemplo:
git commit -m "Corregí el error en el formulario de login"
```
### 6. Sube tu rama al fork
```bash
git push origin nombre-de-tu-rama
```
### 7. Crea un Pull Request
Ve a tu repositorio en GitHub.
Haz clic en Compare & pull request.
Escribe un título y una descripción clara de los cambios.

Envía el pull request para revisión.
