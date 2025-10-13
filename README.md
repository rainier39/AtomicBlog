# AtomicBlog
AtomicBlog is a simple, lightweight blogging software written purely in PHP, HTML, and CSS. No libraries, no frameworks, no JavaScript. I originally wrote it while taking a communication studies class at college. We were supposed to create a blog as part of a semester long project in the class. It was expected that we would use some online service such as weebly for the task, but I decided to challenge myself and create my very own blogging software from scratch. It turned out to be a great decision, resulting in AtomicBlog existing now, and helping me gain experience and skills as a full-stack web developer.

As of the time of writing, AtomicBlog is officially public and in development. I intend on making it suitable for production use, as well as maintaining it in general. That being said, right now it's lacking in features and may contain bugs. Another big area of focus is security since that's a major interest of mine. Beyond that, my design philosophy is keeping AtomicBlog as simple and lightweight as possible, and as easy and intuitive to use as possible. I welcome any/all contributions, but realistically I don't expect any. Please feel free to open an issue at any time regarding anything related to the project.

## Installation
AtomicBlog has so far been developed and tested with Apache2. There are installation instructions for [Apache2](https://github.com/rainier39/AtomicBlog/wiki/Installation-Instructions-(Apache2)). The software is currently broken on Nginx due to issues with the way I do pretty URLs. This will be fixed at some point in the future. I will also update the software so that it works on new PHP versions as they are released. I also intend on testing the software in a wide range of environments.

## Compatibility
Currently only works on Apache2. Will work with any MySQL compatible database software. Should work on any Linux distribution. Probably works on any recent PHP versions but only guarenteed to work on versions that have been tested.

AtomicBlog has been tested on Debian 12 with PHP 8.2.28, Apache/2.4.62, and MariaDB 10.11.11.
