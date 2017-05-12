class: center, middle, inverse

# Hazte un chatbot para Wordpress

(Usando la WP Rest API)

---

class: inverse, center

# @javaguirre

[![:scale 110%](./images/reply.png)](https://www.reply.ai)

---

class: inverse, center

# Bots

* Automatiza tarea
* Imita comportamiento humano

[![:scale 60%](https://media.giphy.com/media/dwiCV5d7qfCqk/giphy.gif)]()

---

class: inverse, center

# Chatbots

![:scale 90%](https://media.giphy.com/media/UukXwaGIamJ1u/giphy.gif)

---

class: inverse, center

# Cambio de paradigma

![:scale 110%](./images/shift.png)

[An introduction to chat bots](http://es.slideshare.net/sohanmaheshwar/an-introduction-to-chat-bots)

---

class: inverse, center

# Por dónde empezamos

![:scale 100%](https://media.giphy.com/media/xTka034bGJ8H7wH1io/giphy.gif)



---

class: inverse, center

# Telegram

![:scale 90%](./images/telegram.png)


---

class: inverse, center

# API.AI

![:scale 110%](./images/apiai.png)

---

class: inverse, center

# WP REST API

![:scale 110%](./images/wp.png)

---

class: inverse, center

# Arquitectura

![:scale 110%](./images/arquitecture.png)

---

class: inverse

# Servicio Web

- `/init` Inicializar la aplicación de Telegram
- `/webhook` Nos llegan los mensajes que llegan a Telegram

---

class: inverse

# Servicios externos

* [Api.AI](https://api.ai/)
* [Telegram](https://telegram.org/)
* [WordPress Rest API](http://v2.wp-api.org/)

---

class: inverse

# WP Rest API

* GET `/posts`      -> Lista posts
* GET `/posts/{id}` -> Ver post
* POST `/posts`     -> Crear nuevo
* PUT `/posts/{id}` -> Editar post

---

class: inverse

# Tecnología

* [Silex](https://silex.sensiolabs.org/), Microframework basado en Symfony
* [Requests](https://github.com/rmccue/Requests), biblioteca para hacer peticiones HTTP
* [Ngrok](https://ngrok.com/), Túnel HTTP a localhost
* [Docker](https://www.docker.com/what-docker), plataforma de contenedores, entorno de desarrollo

---

class: inverse, center

# Manos a la obra

[![:scale 70%](https://media.giphy.com/media/JIX9t2j0ZTN9S/giphy.gif)]()

---

class: inverse, center, middle

# ¡Gracias!
#### Javier Aguirre [@javaguirre](https://javaguirre.me)
#### [Github](https://github.com/javaguirre) | [Twitter](https://twitter.com/javaguirre)
