CF-Scan
=======

Clase PHP para intentar obtener la IP real de un servidor web detras del CDN de CloudFlare.
Hace algo tipo bruteforce buscando subdominios en el dominio principal y comparando la IP que
reportan estos con las de CloudFlare. Tiene soporte IPv6.

