# AtomicBlog
AtomicBlog is a simple, lightweight blogging software written purely in PHP (backend), along with plain HTML, CSS, and JavaScript (frontend). No libraries, no frameworks, minimal JavaScript. I originally wrote it while taking a communication studies class at college. I was supposed to create a blog as part of a semester-long project. It was expected that I would use some online service such as Weebly for the task. But I decided to challenge myself and create my very own blogging software from scratch. It turned out to be a great decision, resulting in AtomicBlog existing now, and helping me gain experience and skills as a full-stack web developer.

At the time of writing, AtomicBlog is public and in development. I intend on making it suitable for production use, as well as maintaining it in general. That being said, right now it's lacking in features and may contain bugs. Another big area of focus is security since that's a major interest of mine. Beyond that, my design philosophy is keeping AtomicBlog as simple and lightweight as possible, and as easy and intuitive to use as possible. I welcome any/all contributions, but realistically I don't expect any. Please feel free to open an issue at any time regarding anything related to the project.

## Installation
AtomicBlog has so far been developed and (mostly) tested with Apache2. There are installation instructions for [Apache2](https://github.com/rainier39/AtomicBlog/wiki/Installation-Instructions-(Apache2)) and [Nginx](https://github.com/rainier39/AtomicBlog/wiki/Installation-Instructions-(Nginx)). I will update the software so that it works on new PHP versions as they are released. I also intend on testing the software in a wide range of environments.

## Compatibility
Aims to be compatible with Apache2 and Nginx. Will work with any MySQL compatible database software. Should work on any Linux distribution. Probably works on any recent PHP versions but only guarenteed to work on versions that have been tested.

AtomicBlog has been tested on:
- Debian 12 (bookworm) with PHP 8.2.29, Apache/2.4.65, and MariaDB 10.11.14.
- Debian 13 (trixie) with PHP 8.4.11, Nginx/1.26.3, and MariaDB 11.8.3.
